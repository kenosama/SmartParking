<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use Illuminate\Http\Request;

class ParkingController extends Controller
{
    public function index()
    {
        return Parking::with('user', 'parkingSpots')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'street' => 'required|string',
            'location_number' => 'required|string',
            'zip_code' => 'required|string',
            'city' => 'required|string',
            'capacity' => 'required|integer',
            'price_per_hour' => 'required|numeric',
            'opening_hours' => 'required|string',
            'opening_days' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        return Parking::create($validated);
    }

    public function show(Parking $parking)
    {
        return $parking->load('user', 'parkingSpots');
    }

    public function update(Request $request, Parking $parking)
    {
        $parking->update($request->all());
        return $parking;
    }

    public function destroy(Parking $parking)
    {
        $parking->delete();
        return response()->noContent();
    }
}