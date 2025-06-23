<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParkingSpotController extends Controller
{
    /**
     * Liste tous les spots de l'utilisateur connecté.
     */
    public function index()
    {
        $spots = ParkingSpot::where('user_id', Auth::id())->with('parking')->get();
        return response()->json($spots);
    }

    /**
     * Crée une nouvelle place de parking.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:10',
            'parking_id' => 'required|exists:parkings,id',
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // Empêche la duplication du même spot dans un même parking
        $exists = ParkingSpot::where('parking_id', $validated['parking_id'])
            ->where('identifier', $validated['identifier'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Cette place existe déjà dans ce parking.'], 409);
        }

        $spot = ParkingSpot::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return response()->json($spot, 201);
    }

    /**
     * Affiche une seule place de parking.
     */
    public function show(ParkingSpot $parkingSpot)
    {
        if ($parkingSpot->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        return response()->json($parkingSpot->load('parking'));
    }

    /**
     * Met à jour une place de parking.
     */
    public function update(Request $request, ParkingSpot $parkingSpot)
    {
        if ($parkingSpot->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $parkingSpot->update($validated);

        return response()->json($parkingSpot);
    }

    /**
     * Supprime une place de parking.
     */
    public function destroy(ParkingSpot $parkingSpot)
    {
        if ($parkingSpot->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        $parkingSpot->delete();

        return response()->json(['message' => 'Place supprimée.']);
    }
}
