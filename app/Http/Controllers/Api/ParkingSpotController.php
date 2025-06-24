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
     * Display a listing of the parking spots created by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Step 1: Retrieve only the spots created by the authenticated user.
        // Step 2: Each spot is related to a parking (belongsTo relationship).
        $spots = ParkingSpot::where('user_id', Auth::id())->with('parking')->get();

        // Step 3: Return the spots as a JSON response.
        return response()->json($spots);
    }

    /**
     * Store newly created parking spots.
     *
     * This method validates input, parses multiple identifiers including ranges,
     * checks capacity and duplicates, creates spots, and returns the created data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Step 1: Validate the submitted data.
        $validated = $request->validate([
            'identifiers' => 'required|string', // Ex: "A1-A5,B1,B2-B3"
            'parking_id' => 'required|exists:parkings,id',
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // Step 2: Retrieve the parking model.
        $parking = \App\Models\Parking::findOrFail($validated['parking_id']);

        // Step 3: Determine default availability based on parking active status.
        $defaultAvailability = $parking->is_active ? $request->boolean('is_available', true) : false;

        // Step 4: Parse the identifiers input into a collection of unique identifiers.
        $identifiers = $this->parseIdentifiers($request->input('identifiers'));

        // Step 5: Check if adding these spots exceeds the parking's total capacity.
        if ($this->isCapacityExceeded($parking, $identifiers->count())) {
            return response()->json(['error' => 'Too many spots compared to capacity.'], 400);
        }

        // Step 6: Check for duplicate identifiers already existing in the database.
        $existing = $this->getDuplicateIdentifiers($parking, $identifiers);

        if (!empty($existing)) {
            return response()->json(['error' => 'The following identifiers already exist: ' . implode(', ', $existing)], 409);
        }

        // Step 7: Create new parking spots.
        $created = [];
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
            $spot->load(['parking', 'user']);
            $created[] = [
                'user' => [
                    'full_name' => $spot->user->first_name . ' ' . $spot->user->last_name,
                    'email' => $spot->user->email,
                ],
                'parking' => [
                    'id' => $spot->parking->id,
                    'name' => $spot->parking->name,
                    'street' => $spot->parking->street,
                    'location_number' => $spot->parking->location_number,
                    'zip_code' => $spot->parking->zip_code,
                    'city' => $spot->parking->city,
                    'country' => $spot->parking->country,
                    'total_capacity' => $spot->parking->total_capacity,
                    'is_open_24h' => $spot->parking->is_open_24h,
                    'opening_hours' => $spot->parking->opening_hours,
                    'opening_days' => $spot->parking->opening_days,
                ],
                'spot' => [
                    'id' => $spot->id,
                    'identifier' => $spot->identifier,
                    'allow_electric_charge' => $spot->allow_electric_charge,
                    'is_available' => $spot->is_available,
                    'per_day_only' => $spot->per_day_only,
                    'price_per_day' => $spot->price_per_day,
                    'price_per_hour' => $spot->price_per_hour,
                ]
            ];
        }

        // Step 8: Return the created spots along with parking and user information.
        return response()->json([
            'parking' => $created[0]['parking'],
            'user' => $created[0]['user'],
            'spots' => collect($created)->pluck('spot'),
            'count' => count($created),
        ], 201);
    }

    /**
     * Display the specified parking spot details including related parking and owner.
     *
     * @param \App\Models\ParkingSpot $parkingSpot
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ParkingSpot $parkingSpot)
    {
        // Step 1: Load related parking and user data for the parking spot.
        $spotWithRelations = $parkingSpot->load('parking', 'user');

        // Step 2: Return the spot and its owner (proprietaire) information.
        return response()->json([
            'spot' => $spotWithRelations,
            'proprietaire' => $parkingSpot->user->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * Update the specified parking spot.
     *
     * Only the spot owner or an admin can update the spot.
     * Validates input and applies updates.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ParkingSpot $parkingSpot
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ParkingSpot $parkingSpot)
    {
        // Step 1: Check if current user is owner or admin.
        if ($parkingSpot->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        // Step 2: Validate the allowed update fields for a parking spot.
        $validated = $request->validate([
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // Step 3: Apply updates to the parking spot.
        $parkingSpot->update($validated);

        // Step 4: Reload related models for a complete response.
        $parkingSpot->load('parking', 'user');

        // Step 5: Return success message and updated spot.
        return response()->json([
            'message' => 'Spot updated.',
            'spot' => $parkingSpot
        ]);
    }

    /**
     * Deactivate the specified parking spot (soft delete).
     *
     * Only the spot owner or an admin can deactivate the spot.
     *
     * @param \App\Models\ParkingSpot $parkingSpot
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ParkingSpot $parkingSpot)
    {
        // Step 1: Check if current user is owner or admin.
        if ($parkingSpot->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        // Step 2: Soft delete: mark the spot as unavailable instead of removing it from the database.
        $parkingSpot->is_available = false;
        $parkingSpot->save();

        // Step 3: Return confirmation message.
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
            $cities = \App\Models\Parking::where('country', $country)
                ->distinct()
                ->pluck('city');
            return response()->json(['cities' => $cities]);
        }

        // Step 3: If zip code is provided, return active parkings and their available spots.
        if ($zip) {
            $parkings = \App\Models\Parking::with(['spots' => function ($q) use ($start, $end) {
                $q->where('is_available', true)
                    ->when($start && $end, function ($query) use ($start, $end) {
                        $this->applyReservationConflictFilter($query, $start, $end);
                    });
            }])
                ->where('zip_code', $zip)
                ->where('is_active', true)
                ->get();

            return response()->json(['parkings' => $parkings]);
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
                ->get();

            return response()->json(['spots' => $spots]);
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
        // Step 1: Filter out spots that have active reservations conflicting with the given date range.
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
        return collect(explode(',', $input))
            ->flatMap(function ($item) {
                $item = trim($item);
                if (preg_match('/^([A-Z])(\d+)-([A-Z])(\d+)$/i', $item, $m) && $m[1] === $m[3]) {
                    $prefix = strtoupper($m[1]);
                    $start = intval($m[2]);
                    $end = intval($m[4]);
                    return collect(range($start, $end))->map(fn($n) => $prefix . $n);
                } elseif (preg_match('/^(\d+)-(\d+)$/', $item, $m)) {
                    return collect(range(intval($m[1]), intval($m[2])));
                } else {
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
        return $parking->spots()
            ->whereIn('identifier', $identifiers)
            ->pluck('identifier')
            ->map(fn($v) => strtoupper($v))
            ->toArray();
    }
}
