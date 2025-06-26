<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpot;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParkingSpotController extends Controller
{
    /**
     * Display a listing of the parking spots accessible by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Step 1: Get the currently authenticated user.
        $user = Auth::user();

        // Step 2: If user is admin, return all parking spots with their parking info.
        if ($user->is_admin) {
            $spots = ParkingSpot::with(['parking.user', 'user'])->get();
        } else {
            // Step 3: Gather IDs of parkings where user is creator or co-owner.
            $createdParkingIds = $user->parkings()->pluck('id')->toArray();
            $coOwnedParkingIds = $user->coOwnedParkings()->select('parkings.id')->pluck('parkings.id')->toArray();
            $accessibleParkingIds = array_unique(array_merge($createdParkingIds, $coOwnedParkingIds));

            // Step 4: If no accessible parkings, return unauthorized error.
            if (empty($accessibleParkingIds)) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }

            // Step 5: Retrieve all spots within accessible parkings.
            $spots = ParkingSpot::whereIn('parking_id', $accessibleParkingIds)
                ->when(
                    !$user->is_admin && empty(array_diff($accessibleParkingIds, $createdParkingIds)),
                    fn ($query) => $query,
                    fn ($query) => $query->where('user_id', $user->id)
                )
                ->with(['parking.user', 'user'])
                ->get();
        }

        // Step 6/7: Use the new formatSpotResponse method for consistent formatting.
        return response()->json($this->formatSpotResponse($spots), 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Store newly created parking spots.
     *
     * Validates input, parses identifiers (including ranges), checks capacity and duplicates,
     * creates spots, and returns the created data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Step 1: Validate the submitted data.
        $validated = $request->validate([
            'identifiers' => 'required|string', // e.g., "A1-A5,B1,B2-B3"
            'parking_id' => 'required|exists:parkings,id',
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // Step 2: Retrieve the parking model and eager-load coOwners.
        $parking = \App\Models\Parking::with('coOwners')->findOrFail($validated['parking_id']);

        // Step 3: Authorize user (must be admin, creator, or co-owner).
        if (!$this->isUserAuthorizedForParking($parking)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        // Step 4: Determine default availability based on parking active status.
        $defaultAvailability = $parking->is_active ? $request->boolean('is_available', true) : false;

        // Step 5: Parse the identifiers input into a collection of unique identifiers.
        $identifiers = $this->parseIdentifiers($request->input('identifiers'));

        // Step 6: Check if adding these spots exceeds the parking's total capacity.
        if ($this->isCapacityExceeded($parking, $identifiers->count())) {
            return response()->json(['error' => 'Too many spots compared to capacity.'], 400);
        }

        // Step 7: Check for duplicate identifiers already existing in the database.
        $existing = $this->getDuplicateIdentifiers($parking, $identifiers);
        if (!empty($existing)) {
            return response()->json(['error' => 'The following identifiers already exist: ' . implode(', ', $existing)], 409);
        }

        // Step 8: Create new parking spots for each identifier.
        $spotIds = [];
        foreach ($identifiers as $identifier) {
            $spot = ParkingSpot::create([
                'identifier' => $identifier,
                'parking_id' => $validated['parking_id'],
                'user_id' => Auth::id(),
                'allow_electric_charge' => $request->boolean('allow_electric_charge', false),
                'is_available' => $defaultAvailability,
                'per_day_only' => $request->boolean('per_day_only', false),
                'price_per_day' => $validated['price_per_day'] ?? null,
                'price_per_hour' => $validated['price_per_hour'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
            $spotIds[] = $spot->id;
        }

        $spots = ParkingSpot::whereIn('id', $spotIds)->with(['parking.user', 'user'])->get();
        return response()->json($this->formatSpotResponse($spots), 201, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Display the specified parking spot details including related parking and owner.
     *
     * @param \App\Models\ParkingSpot $parkingSpot
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ParkingSpot $parkingSpot)
    {
        // Step 1: Charger les relations nécessaires
        $spot = $parkingSpot->load(['parking.user', 'user']);

        // Step 2: Utiliser le même format de sortie que la méthode index
        return response()->json(
            $this->formatSpotResponse(collect([$spot])),
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /**
     * Update the specified parking spot.
     *
     * Only the spot owner, parking creator, co-owner, or an admin can update the spot.
     * Validates input and applies updates.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ParkingSpot $parkingSpot
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ParkingSpot $parkingSpot)
    {
        // Step 1: Get parking for the spot, with its co-owners.
        $parking = $parkingSpot->parking()->with('coOwners')->first();

        // Step 2: Authorize the user.
        if (!$this->isUserAuthorizedForParking($parking)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        // Step 3: Validate fields allowed for update.
        $validated = $request->validate([
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        // Step 4.1: Allow changing user_id only for admin or parking owner.
        if ($request->filled('user_id') && $request->input('user_id') !== $parkingSpot->user_id) {
            $newOwnerId = $request->input('user_id');
            $currentUser = Auth::user();

            if ($currentUser->is_admin || $parking->user_id === $currentUser->id) {
                // Sync co-owner before updating the user_id
                $parkingSpot->syncCoOwner($newOwnerId);
            } else {
                return response()->json(['error' => 'Only the admin or parking owner can reassign the spot.'], 403);
            }
        }

        // Step 4: Update the parking spot (after syncCoOwner for correct old user reference).
        $parkingSpot->update($validated);

        // Step 5: Reload related models for a complete response.
        $spot = $parkingSpot->fresh(['parking.user', 'user']);
        // Step 6: Return updated spot in same format as index() using formatSpotResponse
        return response()->json($this->formatSpotResponse(collect([$spot])), 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Deactivate the specified parking spot (soft delete).
     *
     * Only the spot owner, parking creator, co-owner, or an admin can deactivate the spot.
     *
     * @param \App\Models\ParkingSpot $parkingSpot
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ParkingSpot $parkingSpot)
    {
        // Step 1: Get parking for the spot, with its co-owners.
        $parking = $parkingSpot->parking()->with('coOwners')->first();

        // Step 2: Authorize the user.
        if (!$this->isUserAuthorizedForParking($parking)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        // Step 3: Mark the spot as unavailable (soft delete).
        $parkingSpot->is_available = false;
        $parkingSpot->save();

        // Step 4: Return confirmation message.
        return response()->json(['message' => 'Spot deactivated.']);
    }

    /**
     * Dynamic search for available parking spots based on various parameters.
     *
     * Supports:
     * - country (returns distinct cities)
     * - postal code (returns active parkings and their available spots)
     * - parking ID (returns available spots for that parking)
     * - optional date range for availability check
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Step 1: Retrieve query parameters.
        $country = $request->query('country');
        $zip = $request->query('zip_code');
        $parkingId = $request->query('parking_id');
        $start = $request->query('start_datetime');
        $end = $request->query('end_datetime');

        // Step 2: If country is provided, return distinct cities within that country.
        if ($country) {
            $zip = \App\Models\Parking::where('country', $country)
                ->distinct()
                ->pluck('zip_code');
            return response()->json(['Zip codes' => $zip]);
        }

        // Step 3: If zip code is provided, return active parkings and their available spots.
        if ($zip) {
            $parkings = \App\Models\Parking::with(['spots.user', 'user'])
                ->where('zip_code', $zip)
                ->where('is_active', true)
                ->get();

            $result = $parkings->map(function ($parking) use ($start, $end) {
                $availableSpots = $parking->spots->filter(function ($spot) use ($start, $end) {
                    if (!$spot->is_available) return false;

                    if ($start && $end) {
                        $conflicting = $spot->reservations()
                            ->where('status', 'active')
                            ->where(function ($q) use ($start, $end) {
                                $q->whereBetween('start_datetime', [$start, $end])
                                    ->orWhereBetween('end_datetime', [$start, $end])
                                    ->orWhere(function ($inside) use ($start, $end) {
                                        $inside->where('start_datetime', '<', $start)
                                            ->where('end_datetime', '>', $end);
                                    });
                            })->exists();
                        return !$conflicting;
                    }

                    return true;
                });

                return [
                    'parking' => [
                        'id' => $parking->id,
                        'name' => $parking->name,
                        'address' => trim(
                            $parking->street . ' ' . $parking->location_number . ', ' .
                                $parking->zip_code . ' ' . $parking->city . ', ' .
                                $parking->country
                        ),
                        'total_capacity' => $parking->total_capacity,
                        'is_open_24h' => $parking->is_open_24h,
                        'opening_hours' => $parking->opening_hours,
                        'opening_days' => $parking->opening_days,
                        'owner' => $parking->user ? ($parking->user->first_name . ' ' . $parking->user->last_name) : null,
                        'owner_email' => $parking->user ? $parking->user->email : null,
                        'is_active' => $parking->is_active,
                    ],
                    'Spot_info' => [
                        'number_of_available_spots' => $availableSpots->count(),
                        'price_range_per_day' => $availableSpots->pluck('price_per_day')->filter()->sort()->whenEmpty(
                            fn($c) => 'N/A',
                            fn($c) => "from {$c->first()} to {$c->last()}"
                        ),
                        'price_range_hourly_tariff' => $availableSpots->pluck('price_per_hour')->filter()->sort()->whenEmpty(
                            fn($c) => 'N/A',
                            fn($c) => "from {$c->first()} to {$c->last()}"
                        ),
                    ]
                ];
            });

            return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Step 4: If parking ID is provided, return available spots for that parking.
        if ($parkingId) {
            $spots = \App\Models\ParkingSpot::where('parking_id', $parkingId)
                ->whereHas('parking', function ($q) {
                    $q->where('is_active', true);
                })
                ->where('is_available', true)
                ->when($start && $end, function ($query) use ($start, $end) {
                    $this->applyReservationConflictFilter($query, $start, $end);
                })
                ->with(['parking.user', 'user'])
                ->get();

            $parking = \App\Models\Parking::with('user')->findOrFail($parkingId);

            $response = [
                'parking' => [
                    'id' => $parking->id,
                    'name' => $parking->name,
                    'address' => trim(
                        $parking->street . ' ' . $parking->location_number . ', ' .
                        $parking->zip_code . ' ' . $parking->city . ', ' .
                        $parking->country
                    ),
                    'total_capacity' => $parking->total_capacity,
                    'is_open_24h' => $parking->is_open_24h,
                    'opening_hours' => $parking->opening_hours,
                    'opening_days' => $parking->opening_days,
                    'is_active' => $parking->is_active,
                ],
                'Spot_info' => [
                    'number_of_available_spots' => $spots->count(),
                    'price_range_per_day' => $spots->pluck('price_per_day')->filter()->sort()->whenEmpty(
                        fn($c) => 'N/A',
                        fn($c) => "from {$c->first()} to {$c->last()}"
                    ),
                    'price_range_hourly_tariff' => $spots->pluck('price_per_hour')->filter()->sort()->whenEmpty(
                        fn($c) => 'N/A',
                        fn($c) => "from {$c->first()} to {$c->last()}"
                    ),
                    'spots' => $spots->sortByDesc('price_per_day')->map(fn($spot) => [
                        'id' => $spot->id,
                        'identifier' => $spot->identifier,
                        'allow_electric_charge' => $spot->allow_electric_charge,
                        'is_available' => $spot->is_available,
                        'is_booked' => method_exists($spot, 'is_booked') ? $spot->is_booked() : (property_exists($spot, 'is_booked') ? $spot->is_booked : 0),
                        'per_day_only' => $spot->per_day_only,
                        'price_per_day' => $spot->price_per_day,
                        'price_per_hour' => $spot->price_per_hour,
                        'note' => $spot->note,
                    ])->values()
                ]
            ];

            return response()->json([$response], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Step 5: If none of the required parameters are provided, return error.
        return response()->json(['error' => 'Required parameter missing.'], 400);
    }

    /**
     * Filter query to exclude parking spots with conflicting reservations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $start
     * @param string $end
     * @return void
     */
    private function applyReservationConflictFilter($query, $start, $end)
    {
        // Step 1: Exclude spots that have active reservations conflicting with the given date range.
        $query->whereDoesntHave('reservations', function ($sub) use ($start, $end) {
            $sub->where('status', 'active')
                ->where(function ($conflict) use ($start, $end) {
                    $conflict->whereBetween('start_datetime', [$start, $end])
                        ->orWhereBetween('end_datetime', [$start, $end])
                        ->orWhere(function ($inside) use ($start, $end) {
                            $inside->where('start_datetime', '<', $start)
                                ->where('end_datetime', '>', $end);
                        });
                });
        });
    }

    /**
     * Parse a comma-separated string of identifiers with support for ranges.
     *
     * @param string $input
     * @return \Illuminate\Support\Collection
     */
    private function parseIdentifiers(string $input)
    {
        // Step 1: Split input by commas and parse ranges.
        return collect(explode(',', $input))
            ->flatMap(function ($item) {
                $item = trim($item);
                // Step 2: Handle letter+number ranges like A1-A5 or B10-B1.
                if (preg_match('/^([A-Z])(\d+)-([A-Z])(\d+)$/i', $item, $m)) {
                    if (strtoupper($m[1]) !== strtoupper($m[3])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'identifiers' => "Mismatched letter range: {$item}"
                        ]);
                    }

                    $start = intval($m[2]);
                    $end = intval($m[4]);

                    if ($start > $end) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'identifiers' => "Invalid range: {$item} (start greater than end)"
                        ]);
                    }

                    $prefix = strtoupper($m[1]);
                    return collect(range($start, $end))->map(fn($n) => $prefix . $n);
                }
                // Step 3: Handle plain number ranges like 1-5 or 203-202.
                elseif (preg_match('/^(\d+)-(\d+)$/', $item, $m)) {
                    $start = intval($m[1]);
                    $end = intval($m[2]);

                    if ($start > $end) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'identifiers' => "Invalid range: {$item} (start greater than end)"
                        ]);
                    }

                    return collect(range($start, $end));
                }
                // Step 4: Handle single identifier.
                else {
                    return [$item];
                }
            })
            ->map(fn($id) => strtoupper(trim($id)))
            ->unique();
    }

    /**
     * Check whether creating new spots would exceed parking capacity.
     *
     * @param \App\Models\Parking $parking
     * @param int $newCount
     * @return bool
     */
    private function isCapacityExceeded($parking, int $newCount): bool
    {
        // Step 1: Check if sum of existing and new spots exceeds total capacity.
        return $parking->spots()->count() + $newCount > $parking->total_capacity;
    }

    /**
     * Get list of duplicate identifiers already used in the parking.
     *
     * @param \App\Models\Parking $parking
     * @param \Illuminate\Support\Collection $identifiers
     * @return array
     */
    private function getDuplicateIdentifiers($parking, $identifiers): array
    {
        // Step 1: Return uppercased identifiers that already exist in the parking.
        return $parking->spots()
            ->whereIn('identifier', $identifiers)
            ->pluck('identifier')
            ->map(fn($v) => strtoupper($v))
            ->toArray();
    }

    /**
     * Determine if the current user is authorized to manage this parking.
     *
     * @param \App\Models\Parking $parking
     * @return bool
     */
    private function isUserAuthorizedForParking(\App\Models\Parking $parking): bool
    {
        // Step 1: Get current user.
        $user = Auth::user();
        // Step 2: Check if user is admin, creator, or co-owner.
        return $user->is_admin ||
            $parking->user_id === $user->id ||
            $parking->coOwners->contains('id', $user->id);
    }


    /**
     * Return validation rules for creating or updating a parking spot.
     *
     * @param bool $isCreate
     * @return array
     */
    private function getSpotValidationRules(bool $isCreate = false): array
    {
        $presenceRule = $isCreate ? 'required' : 'sometimes';

        return [
            // Only used during store
            'identifiers' => $isCreate ? 'required|string' : 'prohibited',

            // Used in update only or when editing one at a time
            'identifier' => $isCreate ? 'prohibited' : 'sometimes|string',

            // Shared
            'parking_id' => $isCreate ? 'required|exists:parkings,id' : 'prohibited',
            'allow_electric_charge' => "$presenceRule|boolean",
            'is_available' => "$presenceRule|boolean",
            'per_day_only' => "$presenceRule|boolean",
            'price_per_day' => "$presenceRule|nullable|numeric|min:0",
            'price_per_hour' => "$presenceRule|nullable|numeric|min:0",
            'note' => "$presenceRule|nullable|string|max:255",
        ];
    }

    /**
     * Format a collection of ParkingSpot models into the grouped response structure.
     *
     * @param \Illuminate\Support\Collection $spots
     * @return array
     */
    private function formatSpotResponse($spots)
    {
        $grouped = $spots->groupBy('parking_id');
        $result = [];

        foreach ($grouped as $parkingId => $spotsGroup) {
            $firstSpot = $spotsGroup->first();
            $parking = $firstSpot->parking;
            $owner = $parking->user;

            $parkingArr = [
                'id' => $parking->id,
                'name' => $parking->name,
                'address' => trim(
                    $parking->street . ' ' . $parking->location_number . ', ' .
                        $parking->zip_code . ' ' . $parking->city . ', ' .
                        $parking->country
                ),
                'total_capacity' => $parking->total_capacity,
                'is_open_24h' => $parking->is_open_24h,
                'opening_hours' => $parking->opening_hours,
                'opening_days' => $parking->opening_days,
                'owner' => $owner ? ($owner->first_name . ' ' . $owner->last_name) : null,
                'owner_email' => $owner ? $owner->email : null,
                'is_active' => $parking->is_active,
            ];

            $spotsGroupedByOwner = $spotsGroup->groupBy(function ($spot) {
                return $spot->user ? $spot->user->first_name . ' ' . $spot->user->last_name : 'Non assigné';
            });

            $ownersArr = [];
            foreach ($spotsGroupedByOwner as $ownerName => $ownerSpots) {
                $ownersArr[] = [
                    'owner' => $ownerName,
                    'owner_email' => optional($ownerSpots->first()->user)->email,
                    'spots' => $ownerSpots->map(fn($spot) => [
                        'id'=>$spot->id,
                        'identifier' => $spot->identifier,
                        'allow_electric_charge' => $spot->allow_electric_charge,
                        'is_available' => $spot->is_available,
                        'is_booked' => method_exists($spot, 'is_booked') ? $spot->is_booked() : (property_exists($spot, 'is_booked') ? $spot->is_booked : 0),
                        'per_day_only' => $spot->per_day_only,
                        'price_per_day' => $spot->price_per_day,
                        'price_per_hour' => $spot->price_per_hour,
                        'note' => $spot->note,
                    ])->values()
                ];
            }

            $result[] = [
                'parking' => $parkingArr,
                'owners' => $ownersArr,
            ];
        }

        return $result;
    }
}