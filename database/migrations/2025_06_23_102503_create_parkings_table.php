<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use Illuminate\Http\Request;

class ParkingController extends Controller
{
    /**
     * Affiche tous les parkings (avec relations user et spots)
     */
    public function index()
    {
        return response()->json(
            Parking::with(['user', 'parkingSpots'])->get()
        );
    }

    /**
     * Crée un nouveau parking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'location_number' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'price_per_hour' => 'required|numeric|min:0',
            'opening_hours' => 'required|string',
            'opening_days' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $parking = Parking::create($validated);

        return response()->json($parking->load('user', 'parkingSpots'), 201);
    }

    /**
     * Affiche un parking spécifique
     */
    public function show(Parking $parking)
    {
        return response()->json(
            $parking->load('user', 'parkingSpots')
        );
    }

    /**
     * Met à jour un parking
     */
    public function update(Request $request, Parking $parking)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'street' => 'sometimes|required|string|max:255',
            'location_number' => 'sometimes|required|string|max:50',
            'zip_code' => 'sometimes|required|string|max:20',
            'city' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1',
            'price_per_hour' => 'sometimes|required|numeric|min:0',
            'opening_hours' => 'sometimes|required|string',
            'opening_days' => 'sometimes|required|string',
            'user_id' => 'sometimes|required|exists:users,id',
        ]);

        $parking->update($validated);

        return response()->json($parking->load('user', 'parkingSpots'));
    }

    /**
     * Supprime un parking
     */
    public function destroy(Parking $parking)
    {
        $parking->delete();
        return response()->json(['message' => 'Parking deleted'], 204);
    }
}
