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
use Illuminate\Support\Facades\Log;

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
        Log::info('check validation rules', [
            'isUpdate' => $isUpdate,
        ]);
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
        Log::info('check des logs de validation', [
            'validated' => $validated,
        ]);
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
        Log::info('check des permissions');
        $authUser = Auth::user();
        // Only admins can create/update/delete/index reservations for other users
        if (isset($validated['user_id']) && $validated['user_id'] != $authUser->id && !$authUser->is_admin) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                'message' => 'Unauthorized to create reservation for another user'
            ], 403));
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
        Log::info('Parsing Data');
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
        Log::info('expanding spot identifiers');
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
        Log::info('normalisation des license plates');
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
        Log::info('Fetching the spots 🐕');
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

    // 🔄 Cancel previous reservations associated with a group token according to the connected user.
    protected function cancelPreviousReservations(string $groupToken): void
    {
        Log::info('🔄 Attempting to cancel previous reservations for update', ['group_token' => $groupToken]);

        $reservations = Reservation::where('group_token', $groupToken)
            ->where('status', 'active')
            ->get();

        foreach ($reservations as $reservation) {
            if (auth()->id() === $reservation->user_id) {
                $reservation->status = 'cancelled_by_user';
            } elseif (auth()->user()?->is_admin) {
                $reservation->status = 'cancelled_by_admin';
            } elseif (auth()->id() === $reservation->parkingSpot->owner_id) {
                $reservation->status = 'cancelled_by_owner';
            } else {
                continue;
            }

            $reservation->save();
            Log::info('🔄 Reservation cancelled', ['reservation_id' => $reservation->id, 'new_status' => $reservation->status]);
        }
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
        Log::info('isContinuousMode called');
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
        Log::info('method generateReservationSlots called');
        $slots = [];
        // Check for continuous mode. If so, use explodeContinuousDateRange logic.
        // This method is called for non-continuous mode in the controller, so keep the original logic.
        $current = $startDate->copy();
        $end = $endDate->copy();
        while ($current->lte($end)) {
            if ($startTime < $endTime) {
                $start = Carbon::parse($current->toDateString() . ' ' . $startTime);
                $endSlot = Carbon::parse($current->toDateString() . ' ' . $endTime);
            } else {
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
     * @param string|Carbon $startDate The start date.
     * @param string|Carbon $endDate The end date.
     * @param string $startTime Start time (HH:MM).
     * @param string $endTime End time (HH:MM).
     * @return array Array of ['start' => Carbon, 'end' => Carbon] slots for each day.
     */
    protected function explodeContinuousDateRange($startDate, $endDate, $startTime, $endTime)
    {
        $ranges = [];
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($currentDate->lte($end)) {
            $start = $currentDate->copy()->setTimeFromTimeString(
                $currentDate->isSameDay($startDate) ? $startTime : '00:00'
            );

            $endTimeForDay = $currentDate->isSameDay($endDate)
                ? $currentDate->copy()->setTimeFromTimeString($endTime)
                : $currentDate->copy()->setTimeFromTimeString('23:59');

            $ranges[] = [
                'start' => $start,
                'end' => $endTimeForDay,
            ];

            $currentDate->addDay();
        }

        return $ranges;
    }

    /*
|--------------------------------------------------------------------------
| Methods pour les calculs
|--------------------------------------------------------------------------
*/

    /**
     * Calculates cost and duration for a reservation based on spot type.
     *
     * @param Carbon $startDatetime
     * @param Carbon $endDatetime
     * @param ParkingSpot $spot
     * @param array $processedSlots
     * @return array|null
     */
    public function calculateCostAndDuration(Carbon $startDatetime, Carbon $endDatetime, ParkingSpot $spot, array &$processedSlots = [])
    {
        Log::info("calculation called", [
            "startDatetime" => [$startDatetime],
            "endDatetime" => [$endDatetime],
            "spot" => $spot->identifier
        ]);

        // Deduplication logic
        $slotKey = $spot->id . '-' . $startDatetime->format('Y-m-d');
        if (in_array($slotKey, $processedSlots ?? [])) {
            return null;
        }
        $processedSlots[] = $slotKey;

        if ($spot->per_day_only) {
            // Booking is day-based
            $days = $startDatetime->diffInDays($endDatetime);
            Log::info("variable days", [$days]);
            if ($days === 0) {
                $days = 1; // A single-day reservation still counts as 1 day
            }
            $durationMinutes = $days * 1440;
            $cost = $days * $spot->price_per_day;
        } else {
            // Hour-based
            $durationMinutes = $startDatetime->diffInMinutes($endDatetime);
            $hours = $durationMinutes / 60;

            if ($hours === 6) {
                if ($spot->price_per_hour * 6 < $spot->price_per_day) {
                    Log::info("Special case for exactly 6 hours, using hourly price");
                    $cost = $spot->price_per_hour * 6;
                } else {
                    Log::info("Special case for exactly 6 hours, using daily price");
                    $cost = $spot->price_per_day;
                }
            } elseif ($hours > 6) {
                $hourlyCost = $hours * $spot->price_per_hour;
                if ($hourlyCost < $spot->price_per_day) {
                    Log::info("Duration more than 6 hours, hourly cost is lower than daily, using hourly price");
                    $cost = $hourlyCost;
                } else {
                    Log::info("Duration more than 6 hours, hourly cost >= daily, using daily price");
                    $cost = $spot->price_per_day;
                }
            } else {
                $cost = $hours * $spot->price_per_hour;
                Log::info("Standard hourly rate applied");
            }
        }

        Log::info("Calculated reservation details", [
            'spot_id' => $spot->id,
            'per_day_only' => $spot->per_day_only,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'duration_minutes' => $durationMinutes,
            'cost' => $cost,
        ]);

        return [
            'duration_minutes' => round($durationMinutes, 2),
            'cost' => round($cost, 2),
        ];
    }

    /*
|--------------------------------------------------------------------------
| Methods pour l'ecriture en DB
|--------------------------------------------------------------------------
*/

    /**
     * Process reservation slots and persist reservations.
     *
     * @param array $spots
     * @param array $slots
     * @param array $plates
     * @param int $targetUserId
     * @param string $groupToken
     * @param array $validated
     * @param array $allReservations
     * @param float $totalCost
     * @param float $totalDuration
     * @param array $spotCosts
     * @return void
     */
    protected function processReservationSlots(array $spots, array $slots, array $plates, int $targetUserId, string $groupToken, array $validated, array &$allReservations, float &$totalCost, float &$totalDuration, array &$spotCosts): void
    {
        // Avoid duplicate calculation for same spot and same day
        $processedSlots = [];
        // For each spot and slot, create a reservation if no conflict
        foreach ($spots as $index => $spot) {
            $plate = $plates[$index];
            foreach ($slots as $slot) {
                $startDatetime = \Carbon\Carbon::parse($slot['start']);
                $endDatetime = \Carbon\Carbon::parse($slot['end']);

                if ($spot->per_day_only) {
                    // Override for full-day booking
                    $startDatetime = \Carbon\Carbon::parse($slot['start'])->startOfDay();
                    $endDatetime = \Carbon\Carbon::parse($slot['start'])->endOfDay();
                }

                // Vérification de conflit avec la condition sur le statut 'active'
                $conflict = \App\Models\Reservation::where('parking_spot_id', $spot->id)
                    ->where('status', 'active')
                    ->where(function ($query) use ($startDatetime, $endDatetime) {
                        $query->whereBetween('start_datetime', [$startDatetime, $endDatetime])
                              ->orWhereBetween('end_datetime', [$startDatetime, $endDatetime])
                              ->orWhere(function ($query) use ($startDatetime, $endDatetime) {
                                  $query->where('start_datetime', '<=', $startDatetime)
                                        ->where('end_datetime', '>=', $endDatetime);
                              });
                    })
                    ->exists();
                if ($conflict) {
                    continue;
                }

                $reservationDetails = $this->calculateCostAndDuration($startDatetime, $endDatetime, $spot, $processedSlots);
                if ($reservationDetails === null) {
                    continue;
                }
                $calc = $reservationDetails;
                \Illuminate\Support\Facades\Log::debug("Calculated cost: {$calc['cost']} for spot {$spot->identifier} ({$spot->id})");

                $reservation = \App\Models\Reservation::create([
                    'user_id' => $targetUserId,
                    'parking_id' => $validated['parking_id'],
                    'parking_spot_id' => $spot->id,
                    'license_plate' => $plate,
                    'group_token' => $groupToken,
                    'start_datetime' => $startDatetime,
                    'end_datetime' => $endDatetime,
                    'status' => 'active',
                    // Optionally keep legacy fields if needed:
                    'reserved_date' => $startDatetime->toDateString(),
                    'start_time' => $startDatetime->toTimeString(),
                    'end_time' => $endDatetime->toTimeString(),
                    'duration_minutes' => $calc['duration_minutes'],
                    'cost' => $calc['cost'],
                ]);

                $allReservations[] = $reservation;
                $spotCosts[] = $calc['cost'];
                $totalDuration += $calc['duration_minutes'];
                $totalCost += $calc['cost'];
            }
        }
    }



    /*
|--------------------------------------------------------------------------
| Methods pour le formatage de la réponse
|--------------------------------------------------------------------------
*/

    /**
     * Build reservation summary array for the response.
     *
     * @param array $allReservations
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $startTime
     * @param string $endTime
     * @param int $totalDuration
     * @param float $totalCost
     * @param array $plates
     * @param array $spotCosts
     * @return array
     */
    private function buildReservationSummary(array $allReservations, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate, string $startTime, string $endTime, int $totalDuration, float $totalCost, array $plates, array $spotCosts): array
    {
        return [
            'date' => $startDate->toDateString() . ($endDate->ne($startDate) ? ' → ' . $endDate->toDateString() : ''),
            'time' => $startTime . ' → ' . $endTime,
            'duration_minutes' => $totalDuration,
            'estimated_cost' => round($totalCost, 2),
            'status' => 'active',
            'license_plates' => $plates,
            'spot_costs' => $spotCosts,
            'spots' => collect($allReservations)
                ->groupBy('parking_spot_id')
                ->map(function ($group, $spotId) use ($spotCosts) {
                    $spot = $group->first()->parkingSpot;
                    $total = $group->keys()->map(fn($i) => $spotCosts[$i] ?? 0)->sum();
                    return [
                        'id' => $spot->id,
                        'identifier' => $spot->identifier,
                        'per_day_only' => $spot->per_day_only,
                        'price_per_day' => $spot->price_per_day,
                        'price_per_hour' => $spot->price_per_hour,
                        'allow_electric_charge' => $spot->allow_electric_charge,
                        'total_cost_for_this_spot' => round($total, 2),
                        'note' => $spot->per_day_only
                            ? 'Tarif journalier appliqué ('.$spot->price_per_day.'€/jour). Heures ajustées à 00:00 → 23:59.'
                            : null,
                    ];
                })
                ->values(),
        ];
    }

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
        Log::info('Formatting method called');
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        // Add "Attention" message if any spot is per_day_only
        $hasPerDayOnly = collect($reservations)->contains(fn($r) => $r->parkingSpot->per_day_only);
        $attentionMessage = null;
        if ($hasPerDayOnly) {
            $attentionMessage = "One or more parking_spots are per-day only. Day tariffs will be applied.";
        }

        // Structure the response with reservation information and summary
        $response = [
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
                        $data = [
                            'id' => $spot->id,
                            'identifier' => $spot->identifier,
                            'per_day_only' => $spot->per_day_only,
                            'price_per_day' => $spot->price_per_day,
                            'price_per_hour' => $spot->price_per_hour,
                            'allow_electric_charge' => $spot->allow_electric_charge,
                            'total_cost_for_this_spot' => round($total, 2),
                        ];
                        if ($spot->per_day_only) {
                            $data['Warning'] = "Full day tariff will be applied.";
                        }
                        return $data;
                    })
                    ->values(),
                'date' => $summary['date'],
                'time' => $summary['time'],
                'duration_minutes' => $summary['duration_minutes'],
                'license_plate' => $summary['license_plates'],
                'estimated_cost' => $summary['estimated_cost'],
                'status' => $summary['status'],
            ]
        ];
        if ($attentionMessage) {
            $response['Warning'] = $attentionMessage;
        }
        return response()->json($response, 201, [], $jsonFlags);
    }
    /**
     * Helper to format reservations for the index response.
     *
     * @param \Illuminate\Support\Collection $reservations
     * @return \Illuminate\Support\Collection
     */
    private function formatReservations($reservations)
    {
        return $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'spot_identifier' => $reservation->parkingSpot?->identifier,
                'parking_name' => $reservation->parkingSpot?->parking?->name,
                'user_name' => $reservation->user
                    ? trim($reservation->user->first_name . ' ' . $reservation->user->last_name)
                    : null,
                'user_email' => $reservation->user?->email,
                'start' => $reservation->start_datetime,
                'end' => $reservation->end_datetime,
                'license_plate' => $reservation->license_plate,
                'status' => $reservation->status,
                'group_token' => $reservation->group_token,
            ];
        });
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
        Log::info('Store method initiated', [
            'request_data' => $request->all(),
        ]);
        // Validate request data
        $validated = $request->validate($this->getValidationRules());
        $summary = []; // Ensures $summary is always defined even if no reservation is created

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
            ? $this->explodeContinuousDateRange($startDate, $endDate, $startTime, $endTime)
            : $this->generateReservationSlots($startDate, $endDate, $startTime, $endTime);

        // Used to group related reservations
        $groupToken = Str::uuid()->toString();
        $allReservations = [];
        $spotCosts = [];
        $totalDuration = 0;
        $totalCost = 0;

        $this->processReservationSlots($spots->all(), $slots, $plates, $targetUserId, $groupToken, $validated, $allReservations, $totalCost, $totalDuration, $spotCosts);

        // Prépare summary avec les valeurs issues du calcul (totalCost/durationMinutes)
        $summary = $this->buildReservationSummary(
            $allReservations, $startDate, $endDate, $startTime, $endTime,
            $totalDuration, $totalCost, $plates, $spotCosts
        );

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
        DB::beginTransaction();
        try {
            Log::info('Update method initiated', [
                'request_data' => $request->all(), 'group_token' => $groupToken
            ]);
            // Validate request data
            $validated = $request->validate($this->getValidationRules(isUpdate: true));
            $summary = []; // Ensures $summary is always defined even if no reservation is created

            // 🔄 Cancel previous reservations before creating new ones
            $this->cancelPreviousReservations($groupToken);

            // Perform extra validation on reservation date/time logic
            $this->validateReservationDateLogic($validated);

            // 🔐 Determine the user (enforce permissions)
            $targetUserId = $this->getUserIdAndFilterAuthorization($validated);

            // Inject parking_id from existing reservation if missing
            if (!isset($validated['parking_id'])) {
                $existingReservations = Reservation::where('group_token', $groupToken)->get();
                if ($existingReservations->isEmpty()) {
                    DB::rollBack();
                    return response()->json(['error' => 'No reservations found for the provided group token'], 404);
                }
                $validated['parking_id'] = $existingReservations->first()->parkingSpot->parking_id;
            }

            // 🧹 Normalize and parse parking spot identifiers and license plates
            [$spotIdentifiers, $plates] = $this->parseSpotsAndPlates($validated);

            // 🔍 Fetch previous reservations to determine parking_id
            $reservations = Reservation::where('group_token', $groupToken)->get();

            if ($reservations->isEmpty()) {
                DB::rollBack();
                return response()->json(['error' => 'No reservations found for the given group token.'], 404);
            }

            // 🧠 Extract unique parking_id(s) from previous reservations
            $parkingIds = $reservations->pluck('parkingSpot.parking_id')->unique();

            if ($parkingIds->count() !== 1) {
                DB::rollBack();
                return response()->json(['error' => 'Reservations must all belong to the same parking.'], 422);
            }

            // 🧩 Inject into validated request data
            $validated['parking_id'] = $parkingIds->first();

            // 📦 Fetch the requested parking spots and ensure they exist
            $spots = $this->fetchParkingSpots($validated['parking_id'], $spotIdentifiers);

            // 📆 Handle reservation dates and times
            $startDate = Carbon::parse($validated['reserved_date']);
            $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate;
            $startTime = $validated['start_time'];
            $endTime = $validated['end_time'];

            // Validate date logic
            if ($endDate->lessThan($startDate)) {
                DB::rollBack();
                return response()->json(['error' => 'End date must be after or equal to reserved date'], 422);
            }

            // Compose start and end datetimes
            $startDateTime = Carbon::parse($startDate->toDateString() . ' ' . $startTime);
            $endDateTime = Carbon::parse($endDate->toDateString() . ' ' . $endTime);

            // Validate overall datetime logic
            if ($endDateTime->lte($startDateTime)) {
                DB::rollBack();
                return response()->json(['error' => 'End datetime must be after start datetime'], 422);
            }

            // Generate reservation slots (continuous or daily)
            $slots = $this->isContinuousMode($validated)
                ? $this->explodeContinuousDateRange($startDate, $endDate, $startTime, $endTime)
                : $this->generateReservationSlots($startDate, $endDate, $startTime, $endTime);

            // Used to group related reservations
            $newGroupToken = Str::uuid()->toString();
            $allReservations = [];
            $spotCosts = [];
            $totalDuration = 0;
            $totalCost = 0;

            $this->processReservationSlots($spots->all(), $slots, $plates, $targetUserId, $newGroupToken, $validated, $allReservations, $totalCost, $totalDuration, $spotCosts);

            // Prépare summary avec les valeurs issues du calcul (totalCost/durationMinutes)
            $summary = $this->buildReservationSummary(
                $allReservations,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $totalDuration,
                $totalCost,
                $plates,
                $spotCosts
            );

            DB::commit();
            // Return formatted response
            return $this->formatReservationResponse($allReservations, $summary, 'Reservation updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    /**
     * List reservations based on user role.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('Index method called', [
            'request' => $request->all(),
            'user_role' => $request->user()->role,
        ]);

        $user = $request->user();
        $reservationsQuery = Reservation::with(['user', 'parkingSpot.parking']);

            if ($user->isAdmin()) {
                $reservations = $reservationsQuery->get()->where('status', 'active');
        } elseif ($user->isOwner()) {
            $ownedSpotIds = ParkingSpot::whereHas('parking', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })->pluck('id');

            $reservations = $reservationsQuery
                ->whereIn('parking_spot_id', $ownedSpotIds)
                ->get();
        } else {
            $reservations = $reservationsQuery
                ->where('user_id', $user->id)
                ->get();
        }

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        return response()->json([
            'reservations' => $this->formatReservations($reservations),
        ], 200, [], $jsonFlags);
    }
}