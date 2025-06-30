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
            'user_id' => ($isUpdate ? 'sometimes|required' : 'required') . '|exists:users,id',
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
                $q->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') < ?", [$endDateTime])
                  ->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') > ?", [$startDateTime]);
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
     * Creates one or more new reservations
     */
    public function store(Request $request)
    {
        try {
            // Validate request data: user, parking, time slots, spot identifiers, plates...
            $validated = $request->validate($this->getValidationRules());

            // Étape 1 : Analyse des dates et heures avec gestion robuste des erreurs
            try {
                $reservedDate = $validated['reserved_date'];
                $endDate = $validated['end_date'] ?? $reservedDate;
                $startTime = $validated['start_time'];
                $endTime = $validated['end_time'];

                // Vérifie si les heures sont valides
                if (!Carbon::hasFormat($startTime, 'H:i') || !Carbon::hasFormat($endTime, 'H:i')) {
                    return response()->json(['error' => 'Invalid time format. Use HH:MM.'], 422);
                }

                // Vérifie si les dates sont valides
                if (!Carbon::hasFormat($reservedDate, 'Y-m-d') || !Carbon::hasFormat($endDate, 'Y-m-d')) {
                    return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 422);
                }

                // Construit les datetime
                $startDateTime = Carbon::parse("$reservedDate $startTime");
                $endDateTime = Carbon::parse("$endDate $endTime");

                // Vérifie que la fin est après le début
                if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                    return response()->json([
                        'error' => 'End datetime must be after start datetime. Ensure that start and end times are logical, even across multiple days.'
                    ], 422);
                }

                // Cas particulier : réservation sur un seul jour mais horaire inversé
                if ($reservedDate === $endDate && $startTime >= $endTime) {
                    return response()->json([
                        'error' => 'For same-day reservations, end time must be after start time.'
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to parse reservation datetime: ' . $e->getMessage()
                ], 422);
            }

            // Parse spot identifiers and license plates using helper methods
            $identifiers = $this->expandSpotIdentifiers($validated['parking_spot_identifiers']);
            $plates = $this->normalizeLicensePlates($validated['license_plate']);

            // Ensure each spot has a corresponding license plate
            if (count($identifiers) !== count($plates)) {
                return response()->json([
                    'error' => 'Number of license plates must match number of parking spots.'
                ], 422);
            }

            // Get valid spots matching given identifiers for the selected parking (active only)
            $parkingSpots = \App\Models\ParkingSpot::where('parking_id', $validated['parking_id'])
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

            $reservations = [];
            $adjustment_message = null;
            $startDateTime = null;
            $endDateTime = null;
            $durationMinutes = null;
            // Process each spot for reservation creation
            foreach ($parkingSpots as $index => $spot) {
                // Determine if spot uses per-day-only reservation
                $perDayOnly = $spot->per_day_only;

                // Calculate start and end datetime based on per_day_only flag
                if ($perDayOnly) {
                    $startDateTime = Carbon::parse($validated['reserved_date'])->startOfDay();
                    $endDateTime = Carbon::parse($validated['end_date'] ?? $validated['reserved_date'])->endOfDay();
                    $adjustment_message = 'Reservation times adjusted for daily-only spot. Times set to 00:00 - 23:59.';
                } else {
                    $startDateTime = Carbon::parse($validated['reserved_date'] . ' ' . $validated['start_time']);
                    $endDateTime = Carbon::parse(($validated['end_date'] ?? $validated['reserved_date']) . ' ' . $validated['end_time']);
                }

                // Vérification anti-réservation dans le passé
                if ($startDateTime->isPast()) {
                    return response()->json(['error' => 'You cannot book a reservation in the past.'], 422);
                }

                // Check for reservation conflicts for each requested spot using helper
                if ($this->hasReservationConflict($spot->id, $startDateTime, $endDateTime)) {
                    return response()->json([
                        'error' => "Spot {$spot->identifier} is already reserved for the selected time."
                    ], 409);
                }

                // Prevent duplicate reservation from same user for same spot and time
                $existingReservation = Reservation::where('user_id', $validated['user_id'])
                    ->where('parking_spot_id', $spot->id)
                    ->where('reserved_date', $validated['reserved_date'])
                    ->where('start_time', $perDayOnly ? '00:00' : $validated['start_time'])
                    ->where('end_time', $perDayOnly ? '23:59' : $validated['end_time'])
                    ->whereIn('status', ['active', 'manual_override']) // ignore cancelled or done
                    ->first();

                if ($existingReservation) {
                    return response()->json([
                        'error' => "User already has a reservation for spot {$spot->identifier} at the selected time."
                    ], 409);
                }

                // Create reservation if no conflict and not a duplicate
                $reservation = Reservation::create([
                    'user_id' => $validated['user_id'],
                    'parking_spot_id' => $spot->id,
                    'parking_id' => $validated['parking_id'], // Ensure parking_id is passed explicitly
                    'reserved_date' => $validated['reserved_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'start_time' => $perDayOnly ? '00:00' : $validated['start_time'],
                    'end_time' => $perDayOnly ? '23:59' : $validated['end_time'],
                    'status' => $validated['status'] ?? 'active',
                    'license_plate' => $plates[$index],
                ]);

                // Update spot availability
                $spot->is_available = false;
                $spot->save();

                $reservations[] = $reservation->load('user', 'parkingSpot.parking');
            }

            // Calcul des coûts pour chaque spot réservé et addition du coût total
            $spotCosts = [];
            $totalEstimatedCost = 0.0;
            // Calcul du duration_minutes commun (tous les spots ont la même période)
            $costData = $this->calculateCostAndDuration($parkingSpots[0], $startDateTime, $endDateTime);
            $durationMinutes = $costData['duration_minutes'];
            foreach ($reservations as $i => $reservation) {
                $costData = $this->calculateCostAndDuration($reservation->parkingSpot, $startDateTime, $endDateTime);
                $spotCosts[] = $costData['estimated_cost'];
                $totalEstimatedCost += floatval(str_replace(',', '.', $costData['estimated_cost']));
            }

            return $this->formatReservationResponse($reservations, [
                'date' => $validated['reserved_date'] . ($endDate ? ' → ' . $endDate : ''),
                'time' => $startDateTime->format('H:i') . ' → ' . $endDateTime->format('H:i'),
                'duration_minutes' => $durationMinutes,
                'estimated_cost' => number_format($totalEstimatedCost, 2),
                'status' => $reservations[0]->status,
                'license_plates' => $plates,
                'spot_costs' => $spotCosts,
            ], $adjustment_message ?? 'Reservation successful.');
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'An unexpected error occurred while processing the reservation.',
                'details' => $e->getMessage()
            ], 500);
        }
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
     * Returns the estimated cost and duration in minutes based on a unified rule.
     * Correction complète du calcul de durée et de coût.
     */
    protected function calculateCostAndDuration(\App\Models\ParkingSpot $spot, \Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime): array
    {
        // Handle reservation across midnight properly: from 17:00 to 09:00 next day
        $adjustedEnd = $endDateTime;
        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            $adjustedEnd = $endDateTime->copy()->addDay();
        }
        $durationMinutes = $startDateTime->diffInMinutes($adjustedEnd);

        // Si le spot est uniquement à la journée, on applique le tarif par jour en comptant les jours pleins
        if ($spot->per_day_only) {
            $days = $startDateTime->copy()->startOfDay()->diffInDays($endDateTime->copy()->endOfDay()) + 1;
            $estimatedCost = $days * $spot->price_per_day;
        } else {
            // Sinon, on applique :
            // - tarif journalier si la durée >= 12h (720 minutes)
            // - tarif horaire sinon

            if ($durationMinutes >= 720) {
                $days = ceil($durationMinutes / 1440); // chaque tranche de 24h = 1 jour
                $estimatedCost = $days * $spot->price_per_day;

                // Ajout des heures résiduelles restantes après les jours pleins
                $remainingMinutes = $durationMinutes % 1440;
                if ($remainingMinutes >= 360) {
                    // Une "grosse tranche" >= 6h est traitée comme une journée supplémentaire
                    $estimatedCost += $spot->price_per_day;
                } elseif ($remainingMinutes > 0) {
                    // Sinon, calcul horaire sur la tranche restante
                    $estimatedCost += ceil($remainingMinutes / 60) * $spot->price_per_hour;
                }
            } else {
                // Moins de 12h → tarif horaire
                $estimatedCost = ceil($durationMinutes / 60) * $spot->price_per_hour;
            }
        }

        return [
            'duration_minutes' => $durationMinutes,
            'estimated_cost' => number_format($estimatedCost, 2),
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