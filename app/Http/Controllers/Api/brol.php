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
// Retrieve reservations by group token
$existingReservations = Reservation::where('group_token', $groupToken)->get();

// If none found, return 404
if ($existingReservations->isEmpty()) {
return response()->json(['error' => 'No reservations found for the given group token.'], 404);
}

// Merge PATCH payload with existing values to obtain a complete dataset
$validated = $this->mergeWithExistingValues($validated, $existingReservations->first());

// Validate logical rules related to reservation time ranges
$this->validateReservationDateLogic($validated);

if ($existingReservations->isEmpty()) {
return response()->json(['error' => 'No reservations found for the given group token.'], 404);
}

$authUser = Auth::user();
$targetUserId = $this->getUserIdAndFilterAuthorization($validated);

// Authentification: première vérification critique avant toute modification
if ($existingReservations->first()->user_id !== $authUser->id && !$authUser->is_admin) {
return response()->json(['error' => 'Unauthorized to update this reservation group'], 403);
}

$plateList = [];
$spotIdentifiers = [];

if (isset($validated['parking_spot_identifiers']) && isset($validated['license_plate'])) {
$spotIdentifiers = $this->expandSpotIdentifiers($validated['parking_spot_identifiers']);
$plateList = $this->normalizeLicensePlates($validated['license_plate']);

if (count($spotIdentifiers) !== count($plateList)) {
return response()->json(['error' => 'Number of license plates must match number of parking spots.'], 422);
}
} else {
// Si update partielle, reprendre les valeurs existantes
$spotIdentifiers = $existingReservations->pluck('parkingSpot.identifier')->unique()->toArray();
$plateList = $existingReservations->pluck('license_plate')->unique()->toArray();

if (count($spotIdentifiers) !== count($plateList)) {
return response()->json(['error' => 'Incomplete data: existing reservation group is inconsistent.'], 422);
}
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