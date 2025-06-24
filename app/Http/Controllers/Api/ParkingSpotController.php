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
    public function index()
    {
        // 🔍 Retrieves only the spots created by the authenticated user.
        // Each spot is related to a parking (belongsTo relationship).
        $spots = ParkingSpot::where('user_id', Auth::id())->with('parking')->get();
        return response()->json($spots);
    }

    public function store(Request $request)
    {
        // ✅ Validate the submitted data.
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

        $parking = \App\Models\Parking::findOrFail($validated['parking_id']);
        $existingCount = $parking->spots()->count();

        // If the parking is inactive, newly created spots will be marked as unavailable
        $defaultAvailability = $parking->is_active ? $request->boolean('is_available', true) : false;

        // 📦 Handles multiple identifiers and ranges (e.g., A1-A5, B1-B2, C3)
        $input = $request->input('identifiers');
        $identifiers = collect(explode(',', $input))
            ->flatMap(function ($item) {
                $item = trim($item);
                if (preg_match('/^([A-Z])(\d+)-([A-Z])(\d+)$/i', $item, $m) && $m[1] === $m[3]) {
                    // Range with same letter: A1-A5
                    $prefix = strtoupper($m[1]);
                    $start = intval($m[2]);
                    $end = intval($m[4]);
                    return collect(range($start, $end))->map(fn($n) => $prefix . $n);
                } elseif (preg_match('/^(\d+)-(\d+)$/', $item, $m)) {
                    // Numeric range: 101-105
                    return collect(range(intval($m[1]), intval($m[2])));
                } else {
                    return [$item];
                }
            })
            ->map(fn($id) => strtoupper(trim($id)))
            ->unique();

        // 🚨 Check if the capacity is exceeded
        if ($existingCount + $identifiers->count() > $parking->total_capacity) {
            return response()->json(['error' => 'Trop de places par rapport à la capacité.'], 400);
        }

        // 🎯 Check for duplicates in the database
        $existing = $parking->spots()
            ->whereIn('identifier', $identifiers)
            ->pluck('identifier')
            ->map(fn($v) => strtoupper($v))
            ->toArray();

        if (!empty($existing)) {
            return response()->json(['error' => 'Les identifiants suivants existent déjà : ' . implode(', ', $existing)], 409);
        }

        // 🧱 Create new parking spots
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

        return response()->json([
            'parking' => $created[0]['parking'],
            'user' => $created[0]['user'],
            'spots' => collect($created)->pluck('spot'),
            'count' => count($created),
        ], 201);
    }

    public function show(ParkingSpot $parkingSpot)
    {
        // 🔍 Display the details of a specific parking spot, with parking and owner relations.
        // The load() method loads the relationships defined in the ParkingSpot model.
        return response()->json([
            'spot' => $parkingSpot->load('parking', 'user'),
            'proprietaire' => $parkingSpot->user->only(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, ParkingSpot $parkingSpot)
    {
        // 🔒 Only the spot owner or an admin can update a spot.
        if ($parkingSpot->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        // ✅ Validate the allowed updates for a parking spot.
        $validated = $request->validate([
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // 🔄 Apply updates
        $parkingSpot->update($validated);

        // 🔁 Reload related models for complete response
        $parkingSpot->load('parking', 'user');

        return response()->json([
            'message' => 'Place mise à jour.',
            'spot' => $parkingSpot
        ]);
    }

    public function destroy(ParkingSpot $parkingSpot)
    {
        // 🔒 Only the spot owner or an admin can deactivate a spot.
        if ($parkingSpot->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        // ⛔ Soft delete: mark spot as unavailable instead of removing from DB
        $parkingSpot->is_available = false;
        $parkingSpot->save();

        return response()->json(['message' => 'Place désactivée.']);
    }

    /**
     * Dynamic search for available spots based on:
     * - country (returns cities)
     * - postal code (returns parkings + spots)
     * - parking ID (returns spots from that parking)
     * - optionally: availability check with date range
     */
    public function search(Request $request)
    {
        $country = $request->query('country');
        $zip = $request->query('zip_code');
        $parkingId = $request->query('parking_id');
        $start = $request->query('start_datetime');
        $end = $request->query('end_datetime');

        if ($country) {
            // Return all distinct cities within the specified country
            $cities = \App\Models\Parking::where('country', $country)
                ->distinct()
                ->pluck('city');
            return response()->json(['cities' => $cities]);
        }

        if ($zip) {
            // Return active parkings and their available spots for given zip
            $parkings = \App\Models\Parking::with(['spots' => function ($q) use ($start, $end) {
                $q->where('is_available', true)
                    ->when($start && $end, function ($query) use ($start, $end) {
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
                    });
            }])
                ->where('zip_code', $zip)
                ->where('is_active', true)
                ->get();

            return response()->json(['parkings' => $parkings]);
        }

        if ($parkingId) {
            // Return available spots for a given parking, only if both parking and spot are active
            $spots = \App\Models\ParkingSpot::where('parking_id', $parkingId)
                ->whereHas('parking', function ($q) {
                    $q->where('is_active', true);
                })
                ->where('is_available', true)
                ->when($start && $end, function ($query) use ($start, $end) {
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
                })
                ->get();

            return response()->json(['spots' => $spots]);
        }

        return response()->json(['error' => 'Paramètre requis manquant.'], 400);
    }
}


