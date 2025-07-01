<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ParkingSpot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{

    /*
|--------------------------------------------------------------------------
| Methods for Validations and permissions
|--------------------------------------------------------------------------
*/

    /**
     * Get the validation rules for reservation requests.
     *
     * @param bool $isUpdate Whether the rules are for an update operation.
     * @return array The validation rules.
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'user_id' => 'sometimes|exists:users,id',
            'parking_id' => $isUpdate ? 'prohibited' : 'required|exists:parkings,id',
            'parking_spot_identifiers' => $isUpdate ? 'sometimes|string' : 'required|string',
            'reserved_date' => $isUpdate ? 'sometimes|date' : 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => $isUpdate ? 'sometimes|date_format:H:i' : 'required|date_format:H:i',
            'end_time' => $isUpdate ? 'sometimes|date_format:H:i' : 'required|date_format:H:i',
            'license_plate' => $isUpdate ? 'sometimes|string' : 'required|string',
            'is_continuous' => $isUpdate ? 'sometimes|boolean' : 'required_if:end_date,!null|boolean',
        ];
        return $rules;
    }

    /**
     * Extra validation for reservation date/time logic.
     *
     * @param array $validated The validated request data.
     * @return void
     */
    protected function validateReservationDateLogic(array $validated): void
    {
        // Cas 1: start_time > end_time sans end_date → Overnight sans date explicite
        if (!isset($validated['end_date']) && $validated['start_time'] > $validated['end_time']) {
            abort(422, 'For overnight reservation, please provide an end_date.');
        }

        // Cas 2: end_date == reserved_date mais start_time > end_time → incohérent
        if (
            isset($validated['end_date']) &&
            $validated['reserved_date'] === $validated['end_date'] &&
            $validated['start_time'] > $validated['end_time']
        ) {
            abort(422, 'End time must be after start time for same-day reservations.');
        }

        // Cas 3: Vérifier que les tranches horaires génèrent au moins un slot
        $provisionalStart = Carbon::parse($validated['reserved_date'] . ' ' . $validated['start_time']);
        $provisionalEnd = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'] . ' ' . $validated['end_time'])
            : Carbon::parse($validated['reserved_date'] . ' ' . $validated['end_time']);

        if (!$provisionalStart->lt($provisionalEnd)) {
            abort(422, 'Start time must be before end time.');
        }
    }

    /**
     * Get the user ID for the reservation, enforcing permissions.
     *
     * @param array $validated The validated request data.
     * @return int The user ID for whom the reservation is being made.
     */
    protected function getUserIdAndFilterAuthorization(array $validated): int
    {
        $authUser = Auth::user();
        // Only admins can create/update/delete/index reservations for other users
        if (isset($validated['user_id']) && $validated['user_id'] != $authUser->id && !$authUser->is_admin) {
            abort(403, 'Unauthorized to create reservation for another user');
        }
        return $validated['user_id'] ?? $authUser->id;
    }

    /*
|--------------------------------------------------------------------------
| Methods pour la préparation des entrées
|--------------------------------------------------------------------------
*/
    /**
     * Parse and validate the parking spot identifiers and license plates.
     *
     * @param array $validated The validated request data.
     * @return array [array $spotIdentifiers, array $plates]
     */
    protected function parseSpotsAndPlates(array $validated): array
    {
        // Expand and normalize spot identifiers and license plates
        $spotIdentifiers = $this->expandSpotIdentifiers($validated['parking_spot_identifiers']);
        $plates = $this->normalizeLicensePlates($validated['license_plate']);
        // Ensure one plate per spot
        if (count($spotIdentifiers) !== count($plates)) {
            abort(422, 'Number of license plates must match number of parking spots.');
        }
        return [$spotIdentifiers, $plates];
    }

    /**
     * Expand a string of spot identifiers (with ranges) into an array of identifiers.
     *
     * @param string $identifiers Comma-separated spot identifiers or ranges (e.g., "1,2-4,7").
     * @return array List of all spot identifiers as strings.
     */
    protected function expandSpotIdentifiers(string $identifiers): array
    {
        $expanded = [];

        foreach (explode(',', $identifiers) as $part) {
            $part = strtoupper(trim($part));

            // Match ranges with same alphanumeric prefix, e.g. A1-A5 or B101-B105
            if (preg_match('/^([A-Z]*)(\d+)-\1(\d+)$/', $part, $matches)) {
                $prefix = $matches[1];
                $start = (int) $matches[2];
                $end = (int) $matches[3];

                for ($i = $start; $i <= $end; $i++) {
                    $expanded[] = $prefix . $i;
                }
            }
            // Match purely numeric range (e.g. 1-5)
            elseif (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];

                for ($i = $start; $i <= $end; $i++) {
                    $expanded[] = (string) $i;
                }
            } else {
                // Just a single identifier
                $expanded[] = $part;
            }
        }

        return $expanded;
    }

    /**
     * Normalize a string of license plates into an array of uppercase, alphanumeric plates.
     *
     * @param string $plates Comma-separated license plates.
     * @return array List of normalized license plates.
     */
    protected function normalizeLicensePlates(string $plates): array
    {
        // Split the string, trim, remove non-alphanumerics, and uppercase
        return array_filter(array_map(function ($p) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($p)));
        }, explode(',', $plates)));
    }

    /*
|--------------------------------------------------------------------------
| Methods pour la récupération des entités
|--------------------------------------------------------------------------
*/

    /**
     * Fetch parking spots by parking ID and identifiers, ensuring all exist.
     *
     * @param int $parkingId The parking lot ID.
     * @param array $spotIdentifiers List of spot identifiers.
     * @return \Illuminate\Support\Collection|array Collection of ParkingSpot models.
     */
    protected function fetchParkingSpots(int $parkingId, array $spotIdentifiers)
    {
        // Fetch the requested parking spots
        $spots = ParkingSpot::where('parking_id', $parkingId)
            ->whereIn('identifier', $spotIdentifiers)
            ->get();

        // Ensure all requested spots exist
        if (count($spots) !== count($spotIdentifiers)) {
            abort(422, 'Some parking spots are invalid or not available.');
        }

        return $spots;
    }

    /*
|--------------------------------------------------------------------------
| Methods pour la gestion des dates et créneaux
|--------------------------------------------------------------------------
*/

    /**
     * Determine if reservation is in continuous mode.
     *
     * @param array $validated The validated request data.
     * @return bool True if continuous mode is enabled, false otherwise.
     */
    protected function isContinuousMode(array $validated): bool
    {
        // Check if the reservation is marked as continuous
        return isset($validated['is_continuous']) && $validated['is_continuous'] === true;
    }

    /**
     * Generate reservation slots for each day in the range, handling overnight periods.
     *
     * @param Carbon $startDate The start date.
     * @param Carbon $endDate The end date.
     * @param string $startTime Start time (HH:MM).
     * @param string $endTime End time (HH:MM).
     * @return array Array of ['start' => Carbon, 'end' => Carbon] slots.
     */
    protected function generateReservationSlots(Carbon $startDate, Carbon $endDate, string $startTime, string $endTime): array
    {
        $slots = [];
        $current = $startDate->copy();
        $end = $endDate->copy();

        // Generate a slot for each day (handles overnight if needed)
        while ($current->lte($end)) {
            if ($startTime < $endTime) {
                // Simple day (e.g., 10:00 → 17:00)
                $start = Carbon::parse($current->toDateString() . ' ' . $startTime);
                $endSlot = Carbon::parse($current->toDateString() . ' ' . $endTime);
            } else {
                // Overnight (e.g., 23:00 → 06:00 the next day)
                $start = Carbon::parse($current->toDateString() . ' ' . $startTime);
                $endSlot = Carbon::parse($current->copy()->addDay()->toDateString() . ' ' . $endTime);
            }
            $slots[] = ['start' => $start, 'end' => $endSlot];
            $current->addDay();
        }

        return $slots;
    }

    /**
     * Explode a continuous date range into daily slots with accurate times.
     *
     * @param Carbon $start Start datetime.
     * @param Carbon $end End datetime.
     * @return array Array of ['start' => Carbon, 'end' => Carbon] slots for each day.
     */
    protected function explodeContinuousDateRange(Carbon $start, Carbon $end): array
    {
        $slots = [];
        $current = $start->copy();

        // For continuous mode: cut the interval into daily slots
        while ($current->lt($end)) {
            $slotStart = $current->copy();
            $nextDay = $current->copy()->addDay()->startOfDay();
            $slotEnd = $end->lt($nextDay) ? $end->copy() : $nextDay;

            $slots[] = ['start' => $slotStart, 'end' => $slotEnd];
            $current = $slotEnd->copy();
        }

        return $slots;
    }

    /*
|--------------------------------------------------------------------------
| Methods pour les calculs
|--------------------------------------------------------------------------
*/

    /**
     * Calculate the estimated cost and duration for a reservation on a parking spot.
     *
     * @param ParkingSpot $spot The parking spot.
     * @param Carbon $start Start datetime.
     * @param Carbon $end End datetime.
     * @return array ['duration_minutes' => int, 'estimated_cost' => float]
     */
    protected function calculateCostAndDuration(ParkingSpot $spot, Carbon $start, Carbon $end): array
    {
        // Calculate the duration in minutes
        $duration = $start->diffInMinutes($end);

        // If the spot is only rentable per day, compute cost by days
        if ($spot->per_day_only) {
            $days = $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1;
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round($days * $spot->price_per_day, 2),
            ];
        }

        // If duration is less than 6 hours, use hourly rate
        if ($duration < 360) {
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round(ceil($duration / 60) * $spot->price_per_hour, 2),
            ];
        }

        // If duration is exactly 6 hours, use the cheaper of hourly or daily rate
        if ($duration == 360) {
            $hourlyCost = ceil($duration / 60) * $spot->price_per_hour;
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round(min($hourlyCost, $spot->price_per_day), 2),
            ];
        }

        // If duration is more than 6 hours, use the cheaper of hourly or daily rate
        if ($duration > 360) {
            $hourlyCost = ceil($duration / 60) * $spot->price_per_hour;
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round(min($hourlyCost, $spot->price_per_day), 2),
            ];
        }

        // Fallback (should not be reached)
        return [
            'duration_minutes' => $duration,
            'estimated_cost' => round($spot->price_per_day, 2),
        ];
    }

    /*
|--------------------------------------------------------------------------
| Methods pour le formatage de la réponse
|--------------------------------------------------------------------------
*/

    /**
     * Format the reservation response as a JSON object with summary.
     *
     * @param array $reservations List of Reservation models.
     * @param array $summary Summary data for the reservation(s).
     * @param string $message Message to include in the response.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function formatReservationResponse(array $reservations, array $summary, string $message)
    {
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
        // Structure the response with reservation information and summary
        return response()->json([
            'message' => $message,
            'reservation ids' => collect($reservations)->pluck('id'),
            'reservation made by' => [
                'name' => $reservations[0]->user->full_name ?? ($reservations[0]->user->first_name . ' ' . $reservations[0]->user->last_name),
                'email' => $reservations[0]->user->email,
            ],
            'group_token' => $reservations[0]->group_token,
            'summary' => [
                'parking' => [
                    'id' => $reservations[0]->parkingSpot->parking->id,
                    'name' => $reservations[0]->parkingSpot->parking->name,
                    'zip_code' => $reservations[0]->parkingSpot->parking->zip_code,
                    'city' => $reservations[0]->parkingSpot->parking->city,
                ],
                'number of booked spots' => collect($reservations)->pluck('parking_spot_id')->unique()->count(),
                'spots' => collect($reservations)
                    ->groupBy('parking_spot_id')
                    ->map(function ($group, $spotId) use ($summary) {
                        $spot = $group->first()->parkingSpot;
                        $total = $group->keys()->map(fn($i) => $summary['spot_costs'][$i] ?? 0)->sum();
                        return [
                            'id' => $spot->id,
                            'identifier' => $spot->identifier,
                            'per_day_only' => $spot->per_day_only,
                            'price_per_day' => $spot->price_per_day,
                            'price_per_hour' => $spot->price_per_hour,
                            'allow_electric_charge' => $spot->allow_electric_charge,
                            'total_cost_for_this_spot' => round($total, 2),
                        ];
                    })
                    ->values(),
                'date' => $summary['date'],
                'time' => $summary['time'],
                'duration_minutes' => $summary['duration_minutes'],
                'license_plate' => $summary['license_plates'],
                'estimated_cost' => $summary['estimated_cost'],
                'status' => $summary['status'],
            ]
        ], 201, [], $jsonFlags);
    }

    /**
     * Split a date range into daily segments with accurate start/end times.
     *
     * @param Carbon $start The start datetime.
     * @param Carbon $end The end datetime.
     * @return array Array of ['start' => Carbon, 'end' => Carbon] for each day.
     */
    protected function explodeDateRange(Carbon $start, Carbon $end): array
    {
        $segments = [];
        $current = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        // Iterate over each day in the range
        while ($current->lte($last)) {
            // Determine segment start and end for this day
            $segStart = $current->eq($start->copy()->startOfDay()) ? $start->copy() : $current->copy()->setTime(0, 0);
            $segEnd = $current->eq($end->copy()->startOfDay()) ? $end->copy() : $current->copy()->setTime(23, 59);

            // Only add valid segments
            if ($segEnd->gt($segStart)) {
                $segments[] = ['start' => $segStart, 'end' => $segEnd];
            }

            $current->addDay();
        }

        return $segments;
    }

    /*
|--------------------------------------------------------------------------
| Methodes principales
|--------------------------------------------------------------------------
*/


    /**
     * Store a new reservation (possibly for multiple spots and dates).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate request data
        $validated = $request->validate($this->getValidationRules());

        // Perform extra validation on reservation date/time logic
        $this->validateReservationDateLogic($validated);

        // 🔐 Determine the user (enforce permissions)
        $targetUserId = $this->getUserIdAndFilterAuthorization($validated);

        // 🧹 Normalize and parse parking spot identifiers and license plates
        [$spotIdentifiers, $plates] = $this->parseSpotsAndPlates($validated);

        // 📦 Fetch the requested parking spots and ensure they exist
        $spots = $this->fetchParkingSpots($validated['parking_id'], $spotIdentifiers);

        // 📆 Handle reservation dates and times
        $startDate = Carbon::parse($validated['reserved_date']);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate;
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        // Validate date logic
        if ($endDate->lessThan($startDate)) {
            return response()->json(['error' => 'End date must be after or equal to reserved date'], 422);
        }

        // Compose start and end datetimes
        $startDateTime = Carbon::parse($startDate->toDateString() . ' ' . $startTime);
        $endDateTime = Carbon::parse($endDate->toDateString() . ' ' . $endTime);

        // Validate overall datetime logic
        if ($endDateTime->lte($startDateTime)) {
            return response()->json(['error' => 'End datetime must be after start datetime'], 422);
        }

        // Generate reservation slots (continuous or daily)
        $slots = $this->isContinuousMode($validated)
            ? $this->explodeContinuousDateRange($startDateTime, $endDateTime)
            : $this->generateReservationSlots($startDate, $endDate, $startTime, $endTime);

        // Used to group related reservations
        $groupToken = Str::uuid()->toString();
        $allReservations = [];
        $spotCosts = [];
        $totalDuration = 0;
        $totalCost = 0;

        DB::beginTransaction();
        try {
            // For each spot and slot, create a reservation if no conflict
            foreach ($spots as $index => $spot) {
                $plate = $plates[$index];
                foreach ($slots as $slot) {
                    $start = $slot['start'];
                    $end = $slot['end'];

                    // 🔁 Check for overlaps with existing reservations
                    $hasConflict = Reservation::where('parking_spot_id', $spot->id)
                        ->where(function ($q) use ($start, $end) {
                            $q->where(function ($sub) use ($start, $end) {
                                $sub->where('start_datetime', '<', $end)
                                    ->where('end_datetime', '>', $start);
                            });
                        })
                        ->whereIn('status', ['active', 'manual_override'])
                        ->exists();

                    if ($hasConflict) {
                        // Rollback and return error if conflict is found
                        DB::rollBack();
                        return response()->json(['error' => 'Spot ' . $spot->identifier . ' is already booked during ' . $start->format('Y-m-d H:i') . ' to ' . $end->format('Y-m-d H:i')], 422);
                    }

                    // 💰 Calculate cost and duration for this slot
                    $costData = $this->calculateCostAndDuration($spot, $start, $end);
                    $spotCosts[] = $costData['estimated_cost'];
                    $totalDuration += $costData['duration_minutes'];
                    $totalCost += $costData['estimated_cost'];

                    // Create the reservation
                    $reservation = Reservation::create([
                        'user_id' => $targetUserId,
                        'parking_id' => $validated['parking_id'],
                        'parking_spot_id' => $spot->id,
                        'license_plate' => $plate,
                        'start_datetime' => $start,
                        'end_datetime' => $end,
                        'status' => 'active',
                        'group_token' => $groupToken,
                    ]);

                    $allReservations[] = $reservation;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            // Rollback and return error on exception
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while saving reservations.', 'details' => $e->getMessage()], 500);
        }

        // Prepare summary data for the response
        $summary = [
            'date' => $startDate->toDateString() . ($endDate->ne($startDate) ? ' → ' . $endDate->toDateString() : ''),
            'time' => $startTime . ' → ' . $endTime,
            'duration_minutes' => $totalDuration,
            'estimated_cost' => round($totalCost, 2),
            'status' => 'active',
            'license_plates' => $plates,
            'spot_costs' => $spotCosts,
        ];

        // Return formatted response
        return $this->formatReservationResponse($allReservations, $summary, 'Reservation successful.');
    }

    /**
     * Update all reservations in a group by groupToken.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $groupToken The group token for the reservation group.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $groupToken)
    {
        $validated = $request->validate($this->getValidationRules(true));

        $this->validateReservationDateLogic($validated);

        $existingReservations = Reservation::where('group_token', $groupToken)->get();

        if ($existingReservations->isEmpty()) {
            return response()->json(['error' => 'No reservations found for the given group token.'], 404);
        }

        $authUser = Auth::user();
        $targetUserId = $this->getUserIdAndFilterAuthorization($validated);

        // Authentification: première vérification critique avant toute modification
        if ($existingReservations->first()->user_id !== $authUser->id && !$authUser->is_admin) {
            return response()->json(['error' => 'Unauthorized to update this reservation group'], 403);
        }

        $plateList = $this->normalizeLicensePlates($validated['license_plate']);
        $spotIdentifiers = $this->expandSpotIdentifiers($validated['parking_spot_identifiers']);

        if (count($spotIdentifiers) !== count($plateList)) {
            return response()->json(['error' => 'Number of license plates must match number of parking spots.'], 422);
        }

        $spots = $this->fetchParkingSpots($existingReservations->first()->parking_id, $spotIdentifiers);

        $startDate = Carbon::parse($validated['reserved_date']);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate;
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $isContinuous = $this->isContinuousMode($validated);

        $startDateTime = Carbon::parse($startDate->toDateString() . ' ' . $startTime);
        $endDateTime = Carbon::parse($endDate->toDateString() . ' ' . $endTime);

        $slots = $isContinuous
            ? $this->explodeContinuousDateRange($startDateTime, $endDateTime)
            : $this->generateReservationSlots($startDate, $endDate, $startTime, $endTime);

        $allReservations = [];
        $spotCosts = [];
        $totalDuration = 0;
        $totalCost = 0;

        try {
            $allReservations = DB::transaction(function () use (
                $existingReservations,
                $authUser,
                $targetUserId,
                $plateList,
                $spotIdentifiers,
                $validated,
                $spots,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $isContinuous,
                $startDateTime,
                $endDateTime,
                $slots,
                $groupToken,
                &$spotCosts,
                &$totalDuration,
                &$totalCost
            ) {
                // 1. Annuler les anciennes réservations
                foreach ($existingReservations as $oldRes) {
                    $oldRes->update([
                        'status' => $authUser->is_admin
                            ? 'cancelled_by_admin'
                            : ($oldRes->user_id === $authUser->id ? 'cancelled_by_owner' : 'cancelled_by_user'),
                    ]);
                }

                $allReservations = [];

                // 2. Créer les nouvelles
                foreach ($spots as $index => $spot) {
                    $plate = $plateList[$index];
                    foreach ($slots as $slot) {
                        $start = $slot['start'];
                        $end = $slot['end'];

                        $hasConflict = Reservation::where('parking_spot_id', $spot->id)
                            ->where(function ($q) use ($start, $end) {
                                $q->where('start_datetime', '<', $end)
                                    ->where('end_datetime', '>', $start);
                            })
                            ->whereIn('status', ['active', 'manual_override'])
                            ->where('group_token', '!=', $groupToken)
                            ->exists();

                        if ($hasConflict) {
                            throw new \Exception('Spot ' . $spot->identifier . ' is already booked during ' . $start->format('Y-m-d H:i') . ' to ' . $end->format('Y-m-d H:i'));
                        }

                        $costData = $this->calculateCostAndDuration($spot, $start, $end);
                        $spotCosts[] = $costData['estimated_cost'];
                        $totalDuration += $costData['duration_minutes'];
                        $totalCost += $costData['estimated_cost'];

                        Reservation::where('parking_spot_id', $spot->id)
                            ->where('start_datetime', $start)
                            ->where('end_datetime', $end)
                            ->where('group_token', $groupToken)
                            ->delete();

                        $reservation = Reservation::create([
                            'user_id' => $targetUserId,
                            'parking_id' => $existingReservations->first()->parking_id,
                            'parking_spot_id' => $spot->id,
                            'license_plate' => $plate,
                            'start_datetime' => $start,
                            'end_datetime' => $end,
                            'status' => 'active',
                            'group_token' => $groupToken,
                        ]);

                        $allReservations[] = $reservation;
                    }
                }

                return $allReservations;
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => 'An error occurred while updating reservations.', 'details' => $e->getMessage()], 500);
        }

        $summary = [
            'date' => $startDate->toDateString() . ($endDate->ne($startDate) ? ' → ' . $endDate->toDateString() : ''),
            'time' => $startTime . ' → ' . $endTime,
            'duration_minutes' => $totalDuration,
            'estimated_cost' => round($totalCost, 2),
            'status' => 'active',
            'license_plates' => $plateList,
            'spot_costs' => $spotCosts,
        ];

        return $this->formatReservationResponse($allReservations, $summary, 'Reservation group updated successfully.');
    }
}
