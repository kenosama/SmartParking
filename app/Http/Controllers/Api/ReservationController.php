<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
//TODO reservation multispots. make an error
class ReservationController extends Controller
{
    /**
     * Expands parking spot identifiers from string format like "A1,A2,B1-B3" to array.
     */
    protected function expandSpotIdentifiers(string $identifiers): array
    {
        // Split identifiers by comma and trim whitespace
        $rawIdentifiers = array_map('trim', explode(',', $identifiers));
        $result = [];

        foreach ($rawIdentifiers as $entry) {
            // Detect ranges like "A1-A5" and expand them into [A1, A2, ..., A5]
            if (preg_match('/^([A-Z]+)(\d+)-([A-Z]+)?(\d+)$/i', $entry, $matches)) {
                $prefixStart = strtoupper($matches[1]);
                $startNum = (int)$matches[2];
                $prefixEnd = $matches[3] ? strtoupper($matches[3]) : $prefixStart;
                $endNum = (int)$matches[4];

                // Only expand if prefix matches (avoid cross-letter ranges)
                if ($prefixStart !== $prefixEnd) {
                    continue;
                }

                for ($i = $startNum; $i <= $endNum; $i++) {
                    $result[] = $prefixStart . $i;
                }
            } else {
                $result[] = strtoupper($entry);
            }
        }

        return $result;
    }

    /**
     * Normalizes and returns an array of license plates.
     */
    protected function normalizeLicensePlates(string $licensePlate): array
    {
        // Remove non-alphanumeric chars and convert to uppercase for each plate
        return array_map(function ($p) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $p));
        }, explode(',', $licensePlate));
    }

    /**
     * Returns validation rules for reservation creation or update.
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        return [
            'user_id' => 'sometimes|exists:users,id',
            'parking_id' => ($isUpdate ? 'sometimes|required' : 'required') . '|exists:parkings,id',
            'parking_spot_identifiers' => ($isUpdate ? 'sometimes|required' : 'required') . '|string',
            'reserved_date' => ($isUpdate ? 'sometimes|required' : 'required') . '|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => ($isUpdate ? 'sometimes|required' : 'required') . '|date_format:H:i',
            'end_time' => ($isUpdate ? 'sometimes|required' : 'required') . '|date_format:H:i',
            'status' => 'in:active,cancelled_by_user,cancelled_by_owner,done,manual_override',
            'license_plate' => ($isUpdate ? 'sometimes|required' : 'required') . '|string',
        ];
    }

    /**
     * Validates that the reservation start and end times make sense.
     */
    protected function validateTimeInterval(string $reservedDate, string $startTime, string $endDate, string $endTime): bool
    {
        $startDateTime = \Carbon\Carbon::parse($reservedDate . ' ' . $startTime);
        $endDateTime = \Carbon\Carbon::parse($endDate . ' ' . $endTime);
        return $endDateTime->greaterThan($startDateTime);
    }

    /**
     * Checks if a reservation overlaps with an existing one for the same spot and time.
     * Optionally exclude a reservation ID (for update).
     */
    protected function hasReservationConflict(int $spotId, \Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime, ?int $excludeId = null): bool
    {
        $query = \App\Models\Reservation::where('parking_spot_id', $spotId)
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->where('start_datetime', '<', $endDateTime)
                  ->where('end_datetime', '>', $startDateTime);
            })
            ->whereIn('status', ['active', 'manual_override']);

        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->exists();
    }
    /**
     * Displays all reservations
     */
    public function index()
    {
        return response()->json(
            Reservation::with(['user', 'parkingSpot.parking'])->get()
        );
    }

    /**
     * Creates one or more new reservations (refactored with 7-step logic and helper methods).
     */
    public function store(Request $request)
    {
        try {
            // Step 1: Validate request data
            $validated = $request->validate($this->getValidationRules());

            // Step 2: Determine target user (admin proxy or self only)
            $targetUserId = $this->resolveTargetUser($request, $validated);

            // Step 3: Parse and validate datetime range, get Carbon objects
            [$startDateTime, $endDateTime] = $this->parseDateTimes($validated);
            $dailySegments = $this->explodeDateRange($startDateTime, $endDateTime);

            // Step 4: Parse spot identifiers and license plates, ensure counts match
            $identifiers = $this->expandSpotIdentifiers($validated['parking_spot_identifiers']);
            $plates = $this->normalizeLicensePlates($validated['license_plate']);
            if (count($identifiers) !== count($plates)) {
                return response()->json([
                    'error' => 'Number of license plates must match number of parking spots.'
                ], 422);
            }

            // Step 5: Resolve parking spots (active, for this parking, by identifier)
            $parkingSpots = $this->resolveParkingSpots($validated['parking_id'], $identifiers);

            // Generate a group token for this reservation group
            $groupToken = (string) \Illuminate\Support\Str::uuid();

            // Step 6: Check for conflicts, duplicates, create reservations (one per day and per spot)
            $reservations = [];
            $spotCosts = [];
            $totalCost = 0;
            $durationMinutes = 0;

            foreach ($parkingSpots as $i => $spot) {
                $plate = $plates[$i];

                // Prevent reservation in the past
                if ($startDateTime->isPast()) {
                    return response()->json(['error' => 'You cannot book a reservation in the past.'], 422);
                }

                foreach ($dailySegments as $segment) {
                    $dailyStart = $segment['start'];
                    $dailyEnd = $segment['end'];

                    if ($dailyEnd->lessThanOrEqualTo($dailyStart)) {
                        continue;
                    }

                    // Check for conflict
                    if ($this->hasReservationConflict($spot->id, $dailyStart, $dailyEnd)) {
                        return response()->json([
                            'error' => "Spot {$spot->identifier} is already reserved for {$dailyStart->toDateString()}."
                        ], 409);
                    }

                    $reservation = Reservation::create([
                        'user_id' => $targetUserId,
                        'parking_id' => $validated['parking_id'],
                        'parking_spot_id' => $spot->id,
                        'start_datetime' => $dailyStart,
                        'end_datetime' => $dailyEnd,
                        'license_plate' => $plate,
                        'status' => 'active',
                        'group_token' => $groupToken,
                    ]);

                    $spot->is_available = false;
                    $spot->save();

                    $costData = $this->calculateCostAndDuration($spot, $dailyStart, $dailyEnd);
                    $spotCosts[] = $costData['estimated_cost'];
                    $totalCost += $costData['estimated_cost'];
                    $durationMinutes += $costData['duration_minutes'];

                    $reservations[] = $reservation->load('user', 'parkingSpot.parking');
                }
            }

            // Summary
            $summary = [
                'date' => $startDateTime->format('Y-m-d') . ($endDateTime->format('Y-m-d') !== $startDateTime->format('Y-m-d') ? ' → ' . $endDateTime->format('Y-m-d') : ''),
                'time' => $startDateTime->format('H:i') . ' → ' . $endDateTime->format('H:i'),
                'duration_minutes' => $durationMinutes,
                'license_plates' => $plates,
                'spot_costs' => $spotCosts,
                'estimated_cost' => number_format($totalCost, 2),
                'status' => 'active',
            ];

            return $this->formatReservationResponse($reservations, $summary, 'Reservation successful.');
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'An unexpected error occurred while processing the reservation.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Resolve the target user for reservation (enforces admin proxy rule).
     */
    protected function resolveTargetUser(Request $request, array $validated): int
    {
        $currentUser = $request->user();
        $targetUserId = $validated['user_id'] ?? $currentUser->id;

        if (array_key_exists('user_id', $validated) && !$currentUser->is_admin && $validated['user_id'] != $currentUser->id) {
            abort(response()->json([
                'error' => 'You are not authorized to create a reservation for another user.'
            ], 403));
        }

        return $targetUserId;
    }

    /**
     * Helper: Parse and validate reservation datetimes, return [start, end] as Carbon objects.
     */
    protected function parseDateTimes(array $validated): array
    {
        $reservedDate = $validated['reserved_date'];
        $endDate = $validated['end_date'] ?? $reservedDate;
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        if (!\Carbon\Carbon::hasFormat($startTime, 'H:i') || !\Carbon\Carbon::hasFormat($endTime, 'H:i')) {
            abort(response()->json(['error' => 'Invalid time format. Use HH:MM.'], 422));
        }

        if (!\Carbon\Carbon::hasFormat($reservedDate, 'Y-m-d') || !\Carbon\Carbon::hasFormat($endDate, 'Y-m-d')) {
            abort(response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 422));
        }

        $startDateTime = \Carbon\Carbon::parse("$reservedDate $startTime");
        $endDateTime = \Carbon\Carbon::parse("$endDate $endTime");

        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            abort(response()->json(['error' => 'End datetime must be after start datetime. Ensure that start and end times are logical, even across multiple days.'], 422));
        }

        if ($reservedDate === $endDate && $startTime >= $endTime) {
            abort(response()->json(['error' => 'For same-day reservations, end time must be after start time.'], 422));
        }

        return [$startDateTime, $endDateTime];
    }

    /**
     * Explodes a start → end datetime range into daily segments for split reservations.
     * Returns an array of ['start' => Carbon, 'end' => Carbon] per day.
     */
    protected function explodeDateRange(\Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime): array
    {
        $segments = [];

        $currentDay = $startDateTime->copy()->startOfDay();
        $endDay = $endDateTime->copy()->startOfDay();

        while ($currentDay->lte($endDay)) {
            // Début réel pour le premier jour
            $start = $currentDay->eq($startDateTime->copy()->startOfDay())
                ? $startDateTime->copy()
                : $currentDay->copy()->setTime(0, 0);

            // Fin réelle pour le dernier jour
            $end = $currentDay->eq($endDateTime->copy()->startOfDay())
                ? $endDateTime->copy()
                : $currentDay->copy()->setTime(23, 59);

            // Si l'intervalle est valide
            if ($end->gt($start)) {
                $segments[] = [
                    'start' => $start,
                    'end' => $end,
                ];
            }

            $currentDay->addDay();
        }

        return $segments;
    }

    /**
     * Helper: Resolve parking spots for a parking and list of identifiers, ensure all are valid/active.
     */
    protected function resolveParkingSpots(int $parkingId, array $identifiers)
    {
        $spots = \App\Models\ParkingSpot::where('parking_id', $parkingId)
            ->whereHas('parking', fn ($q) => $q->where('is_active', true))
            ->whereIn('identifier', $identifiers)
            ->get();

        if (count($spots) !== count($identifiers)) {
            abort(response()->json(['error' => 'One or more parking spot identifiers are invalid for the selected parking.'], 422));
        }

        return $spots;
    }

    /**
     * Displays a specific reservation
     */
    public function show(Reservation $reservation)
    {
        return response()->json(
            $reservation->load('user', 'parkingSpot.parking')
        );
    }

    /**
     * Updates a reservation
     */
    public function update(Request $request, Reservation $reservation)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Only allow admins, the reservation owner, or the parking spot owner to update
        if (
            !$currentUser->is_admin &&
            $currentUser->id !== $reservation->user_id &&
            $currentUser->id !== $reservation->parkingSpot->user_id
        ) {
            return response()->json(['error' => 'Unauthorized to update this reservation.'], 403);
        }

        // Validate incoming data for update
        $validated = $request->validate($this->getValidationRules(true));

        // Use current reservation values as fallback for fields not provided
        $user_id = $validated['user_id'] ?? $reservation->user_id;
        $parking_id = $validated['parking_id'] ?? $reservation->parkingSpot->parking_id;
        $reserved_date = $validated['reserved_date'] ?? $reservation->reserved_date;
        $end_date = array_key_exists('end_date', $validated)
            ? $validated['end_date']
            : ($reservation->end_date ?? $reserved_date);
        $start_time = $validated['start_time'] ?? $reservation->start_time;
        $end_time = $validated['end_time'] ?? $reservation->end_time;
        $status = $validated['status'] ?? $reservation->status;
        $spot_identifiers_str = $validated['parking_spot_identifiers'] ?? $reservation->parkingSpot->identifier;
        $license_plate_str = $validated['license_plate'] ?? $reservation->license_plate;

        // Validate interval using helper
        if ($reserved_date && $start_time && $end_time) {
            $intervalEndDate = $end_date ?? $reserved_date;
            if (!$this->validateTimeInterval($reserved_date, $start_time, $intervalEndDate, $end_time)) {
                return response()->json(['error' => 'End time must be after start time.'], 422);
            }
        }

        // Parse spot identifiers and license plates using helpers
        $identifiers = $this->expandSpotIdentifiers($spot_identifiers_str);
        $plates = $this->normalizeLicensePlates($license_plate_str);

        // Ensure each spot has a corresponding license plate
        if (count($identifiers) !== count($plates)) {
            return response()->json([
                'error' => 'Number of license plates must match number of parking spots.'
            ], 422);
        }

        // Retrieve all matching spots (active only, for parking_id)
        $parkingSpots = \App\Models\ParkingSpot::where('parking_id', $parking_id)
            ->whereHas('parking', function ($query) {
                $query->where('is_active', true);
            })
            ->whereIn('identifier', $identifiers)
            ->get();

        if (count($parkingSpots) !== count($identifiers)) {
            return response()->json([
                'error' => 'One or more parking spot identifiers are invalid for the selected parking.'
            ], 422);
        }

        // Map identifiers to ParkingSpot models for lookup
        $spotMap = [];
        foreach ($parkingSpots as $spot) {
            $spotMap[$spot->identifier] = $spot;
        }

        // Find which index in identifiers corresponds to this reservation
        $current_identifier = $reservation->parkingSpot->identifier;
        $update_index = null;
        foreach ($identifiers as $idx => $identifier) {
            if ($identifier === $current_identifier) {
                $update_index = $idx;
                break;
            }
        }
        // If not found, just use first (if changing spot)
        if ($update_index === null) {
            $update_index = 0;
        }
        $target_identifier = $identifiers[$update_index];
        $target_plate = $plates[$update_index];
        $target_spot = $spotMap[$target_identifier] ?? null;
        if (!$target_spot) {
            return response()->json([
                'error' => 'Target parking spot not found or not available.'
            ], 422);
        }

        // Determine reservation interval and adjust for per-day-only spots
        $perDayOnly = $target_spot->per_day_only;
        if ($perDayOnly) {
            $startDateTime = \Carbon\Carbon::parse($reserved_date)->startOfDay();
            $endDateTime = \Carbon\Carbon::parse($end_date ?? $reserved_date)->endOfDay();
            $new_start_time = '00:00';
            $new_end_time = '23:59';
            $adjustment_message = 'Reservation times adjusted for daily-only spot. Times set to 00:00 - 23:59.';
        } else {
            $startDateTime = \Carbon\Carbon::parse($reserved_date . ' ' . $start_time);
            $endDateTime = \Carbon\Carbon::parse(($end_date ?? $reserved_date) . ' ' . $end_time);
            $new_start_time = $start_time;
            $new_end_time = $end_time;
            $adjustment_message = null;
        }

        // Vérification anti-réservation dans le passé
        if ($startDateTime->isPast()) {
            return response()->json(['error' => 'You cannot book a reservation in the past.'], 422);
        }

        // Check for reservation conflict using helper, excluding current reservation
        if ($this->hasReservationConflict($target_spot->id, $startDateTime, $endDateTime, $reservation->id)) {
            return response()->json([
                'error' => "Spot {$target_spot->identifier} is already reserved for the selected time."
            ], 409);
        }

        // Update reservation with new data
        $reservation->user_id = $user_id;
        $reservation->parking_spot_id = $target_spot->id;
        $reservation->reserved_date = $reserved_date;
        $reservation->end_date = $end_date ?? null;
        $reservation->start_time = $new_start_time;
        $reservation->end_time = $new_end_time;
        $reservation->status = $status;
        $reservation->license_plate = $target_plate;
        $reservation->save();

        // Mark spot as unavailable
        $target_spot->is_available = false;
        $target_spot->save();

        // Calcul coût et résumé
        $cost = $this->calculateReservationCost($target_spot, $startDateTime, $endDateTime);
        $summary = [
            'spot' => $target_spot->identifier,
            'date' => $reserved_date . ($end_date ? ' → ' . $end_date : ''),
            'time' => $startDateTime->format('H:i') . ' → ' . $endDateTime->format('H:i'),
            'duration_minutes' => $startDateTime->diffInMinutes($endDateTime),
            'estimated_cost' => number_format($cost, 2),
            'status' => $reservation->status,
        ];

        $responseData = $reservation->load('user', 'parkingSpot.parking');
        return response()->json([
            'message' => $adjustment_message ?? 'Reservation updated successfully.',
            'summary' => $summary,
            'reservation' => $responseData
        ]);
    }

    /**
     * Cancels a reservation based on the current user's role.
     */
    public function cancel(Reservation $reservation)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if ($reservation->status !== 'active') {
            return response()->json([
                'message' => 'Reservation is already finalized or cancelled.',
                'status' => $reservation->status
            ], 200);
        }

        if ($currentUser->is_admin) {
            $reservation->status = 'cancelled_by_admin';
            $reservation->save();

            return response()->json([
                'message' => 'Reservation cancelled by admin.',
                'status' => $reservation->status
            ], 200);
        }

        if ($currentUser->id === $reservation->user_id) {
            $reservation->status = 'cancelled_by_user';
            $reservation->save();

            return response()->json([
                'message' => 'Reservation cancelled by user.',
                'status' => $reservation->status
            ], 200);
        }

        if ($currentUser->id === $reservation->parkingSpot->user_id) {
            $reservation->status = 'cancelled_by_owner';
            $reservation->save();

            return response()->json([
                'message' => 'Reservation cancelled by spot owner.',
                'status' => $reservation->status
            ], 200);
        }

        return response()->json([
            'error' => 'Unauthorized to cancel this reservation.'
        ], 403);
    }

    /**
     * Soft cancels a reservation by updating its status (logical soft delete)
     */
    public function destroy(Reservation $reservation)
    {
        // If the reservation is already cancelled or completed, no update needed
        if (in_array($reservation->status, ['cancelled_by_user', 'cancelled_by_owner', 'done'])) {
            return response()->json([
                'message' => 'Reservation is already finalized or cancelled.'
            ], 200);
        }

        // Get the owner ID of the parking associated with this reservation
        $spotOwnerId = $reservation->parkingSpot->parking->user_id ?? null;

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Prevent an admin from cancelling if not authorized (for info only)
        if ($currentUser->is_admin && $currentUser->id !== $reservation->user_id && $currentUser->id !== ($reservation->parkingSpot->parking->user_id ?? null)) {
            // Admins can cancel anytime, so this check is purely informative if we want to use it later
            // return response()->json(['error' => 'Admin override not authorized'], 403);
        }

        // Admin can do anything, skip checks
        if ($currentUser->is_admin) {
            $reservation->status = 'cancelled_by_admin';
            $reservation->save();

            return response()->json([
                'message' => 'Reservation cancelled by admin.',
                'status' => $reservation->status
            ], 200);
        }

        if ($currentUser->id === $spotOwnerId) {
            $now = now();
            $reservationDateTime = Carbon::parse($reservation->reserved_date . ' ' . $reservation->start_time);

            if ($reservationDateTime->diffInHours($now) <= 48) {
                $reservation->status = 'cancelled_by_owner';
                $reservation->save();

                return response()->json([
                    'message' => 'Reservation cancelled by parking owner within 48h before start.',
                    'status' => $reservation->status
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Parking owner can only cancel within 48h before the reservation start time.'
                ], 403);
            }
        }

        // Determine who cancels: user, admin or other
        if ($currentUser->id === $reservation->user_id) {
            $now = now();
            $reservationDateTime = Carbon::parse($reservation->reserved_date . ' ' . $reservation->start_time);

            // User can cancel only if at least 24h before reservation start
            if ($reservationDateTime->diffInHours($now) >= 24) {
                $reservation->status = 'cancelled_by_user';
            } else {
                return response()->json(['error' => 'You can only cancel your reservation at least 24h in advance.'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized to cancel this reservation.'], 403);
        }

        $reservation->save();

        return response()->json(['message' => 'Reservation cancelled successfully.', 'status' => $reservation->status], 200);
    }

    /**
     * Ends a reservation early by user or admin, marks reservation done and spot available
     */
    public function endReservation(Request $request, Reservation $reservation)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->is_admin && $currentUser->id !== $reservation->user_id) {
            return response()->json(['error' => 'Unauthorized to end this reservation early.'], 403);
        }

        if ($reservation->status !== 'active') {
            return response()->json(['error' => 'Only active reservations can be ended early.'], 400);
        }

        $reservation->status = 'done';
        $reservation->save();

        // Mark spot as available again
        $spot = $reservation->parkingSpot;
        $spot->is_available = true;
        $spot->save();

        return response()->json(['message' => 'Reservation ended successfully.', 'status' => $reservation->status], 200);
    }

    /**
     * Allows an admin to manually occupy a parking spot without assigning a user (temporary block)
     */
    /**
     * Allows an admin to manually occupy a parking spot without assigning a user (temporary block)
     */
    public function manualOccupy(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Only admins may manually occupy a spot
        if (!$currentUser->is_admin) {
            return response()->json(['error' => 'Only admins can manually occupy spots.'], 403);
        }

        // Validate request data for manual occupation
        $validated = $request->validate([
            'parking_spot_id' => 'required|exists:parking_spots,id',
            'reserved_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $spot = \App\Models\ParkingSpot::find($validated['parking_spot_id']);

        // Validate that the reservation interval is logical (start < end)
        $endDate = $validated['end_date'] ?? $validated['reserved_date'];
        if (!$this->validateTimeInterval($validated['reserved_date'], $validated['start_time'], $endDate, $validated['end_time'])) {
            return response()->json(['error' => 'End time must be after start time.'], 422);
        }

        // Check if spot is already occupied or reserved for the given time using helper
        $startDateTime = Carbon::parse($validated['reserved_date'] . ' ' . $validated['start_time']);
        $endDateTime = Carbon::parse(($validated['end_date'] ?? $validated['reserved_date']) . ' ' . $validated['end_time']);

        if ($this->hasReservationConflict($spot->id, $startDateTime, $endDateTime)) {
            return response()->json([
                'error' => "Spot {$spot->identifier} is already reserved or occupied for the selected time."
            ], 409);
        }

        // Create a manual reservation without user and license plate, status 'manual_override'
        $reservation = \App\Models\Reservation::create([
            'user_id' => null,
            'parking_spot_id' => $spot->id,
            'reserved_date' => $validated['reserved_date'],
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'manual_override',
            'license_plate' => null,
        ]);

        // Mark spot as unavailable
        $spot->is_available = false;
        $spot->save();

        return response()->json([
            'message' => 'Spot manually occupied successfully.',
            'reservation' => $reservation->load('parkingSpot.parking')
        ], 201);
    }


    /**
     * Calcule le coût estimé d'une réservation corrigé (localisé).
     */
    protected function calculateReservationCost(\App\Models\ParkingSpot $spot, \Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime): float
    {
        if ($spot->per_day_only) {
            $days = $startDateTime->copy()->startOfDay()->diffInDays($endDateTime->copy()->endOfDay()) + 1;
            return $days * $spot->price_per_day;
        }

        $days = $startDateTime->copy()->startOfDay()->diffInDays($endDateTime->copy()->endOfDay()) + 1;

        // Début et fin à la même heure chaque jour
        $startTime = $startDateTime->format('H:i');
        $endTime = $endDateTime->format('H:i');
        $dailyDuration = \Carbon\Carbon::createFromFormat('H:i', $startTime)
            ->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i', $endTime));

        if ($dailyDuration >= 360) {
            return $days * $spot->price_per_day;
        } else {
            // Sinon, calcul à l'heure chaque jour
            $totalMinutes = $dailyDuration * $days;
            $hours = ceil($totalMinutes / 60);
            return $hours * $spot->price_per_hour;
        }
    }

    /**
     * Returns the estimated cost and duration in minutes based on the new precise logic.
     */
    protected function calculateCostAndDuration(\App\Models\ParkingSpot $spot, \Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime): array
    {
        // Total duration in minutes
        $durationMinutes = $startDateTime->diffInMinutes($endDateTime);

        // If per day only
        if ($spot->per_day_only) {
            $days = $startDateTime->copy()->startOfDay()->diffInDays($endDateTime->copy()->endOfDay()) + 1;
            return [
                'duration_minutes' => $durationMinutes,
                'estimated_cost' => round($days * $spot->price_per_day, 2),
            ];
        }

        // Logic for hourly or daily pricing
        // Case 1: less than 6h -> hourly
        if ($durationMinutes < 360) {
            $hours = ceil($durationMinutes / 60);
            return [
                'duration_minutes' => $durationMinutes,
                'estimated_cost' => round($hours * $spot->price_per_hour, 2),
            ];
        }

        // Case 2: exactly 6h
        if ($durationMinutes === 360) {
            $hourlyCost = 6 * $spot->price_per_hour;
            return [
                'duration_minutes' => $durationMinutes,
                'estimated_cost' => round(min($hourlyCost, $spot->price_per_day), 2),
            ];
        }

        // Case 3: more than 6h -> new daily/hourly cost logic
        $days = $startDateTime->copy()->startOfDay()->diffInDays($endDateTime->copy()->endOfDay()) + 1;
        $startTime = $startDateTime->format('H:i');
        $endTime = $endDateTime->format('H:i');

        $dailyDuration = \Carbon\Carbon::createFromFormat('H:i', $startTime)
            ->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i', $endTime));

        if ($dailyDuration >= 360) {
            $estimated_cost = $days * $spot->price_per_day;
        } else {
            $totalMinutes = $dailyDuration * $days;
            $hours = ceil($totalMinutes / 60);
            $estimated_cost = $hours * $spot->price_per_hour;
        }

        return [
            'duration_minutes' => $durationMinutes,
            'estimated_cost' => round($estimated_cost, 2),
        ];
    }


    /**
     * Formats the reservation response into a structured, pretty JSON block.
     */
    protected function formatReservationResponse(array $reservations, array $summary, string $message)
    {
        return response()->json([
            'message' => $message,
            'reservation ids' => collect($reservations)->pluck('id'),
            'reservation made by' => [
                'name' => $reservations[0]->user->full_name ?? ($reservations[0]->user->first_name . ' ' . $reservations[0]->user->last_name),
                'email' => $reservations[0]->user->email,
            ],
            'summary' => [
                'parking' => [
                    'id' => $reservations[0]->parkingSpot->parking->id,
                    'name' => $reservations[0]->parkingSpot->parking->name,
                    'zip_code' => $reservations[0]->parkingSpot->parking->zip_code,
                    'city' => $reservations[0]->parkingSpot->parking->city,
                ],
                'number of booked spots' => count($reservations),
                'spots' => collect($reservations)->map(function ($r, $i) use ($summary) {
                    return [
                        'id' => $r->parkingSpot->id,
                        'identifier' => $r->parkingSpot->identifier,
                        'per_day_only' => $r->parkingSpot->per_day_only,
                        'price_per_day' => $r->parkingSpot->price_per_day,
                        'price_per_hour' => $r->parkingSpot->price_per_hour,
                        'allow_electric_charge' => $r->parkingSpot->allow_electric_charge,
                        'total_cost_for_this_spot' => $summary['spot_costs'][$i] ?? 'N/A',
                    ];
                }),
                'date' => $summary['date'],
                'time' => $summary['time'],
                'duration_minutes' => $summary['duration_minutes'],
                'license_plate' => $summary['license_plates'],
                'estimated_cost' => $summary['estimated_cost'],
                'status' => $summary['status'],
            ]
        ], 201);
    }
}