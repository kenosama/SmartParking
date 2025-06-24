<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use App\Models\Parking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @extends \Illuminate\Routing\Controller
 */
class ParkingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        return Parking::with('user', 'parkingSpots')->get();
    }

    public function store(Request $request)
    {
        // 🔄 Normalisation de la chaîne "opening_days" : on remplace les tirets par des virgules
        if ($request->has('opening_days')) {
            $request->merge([
                'opening_days' => str_replace('-', ',', $request->input('opening_days')),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'required|string',
            'location_number' => 'required|string',
            'zip_code' => 'required|string',
            'city' => 'required|string',
            'country'=> 'required|string',
            'total_capacity' => 'required|integer',
            'is_open_24h'=> 'required|boolean',
            'opening_hours' => 'nullable|string|required_if:is_open_24h,false',
            'opening_days' => 'nullable|string|required_if:is_open_24h,false|regex:/^([1-7](,[1-7])*)?$/'
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;

        return Parking::create($validated);
    }

    public function show(Parking $parking)
    {
        return $parking->load('user', 'parkingSpots');
    }

    public function update(Request $request, Parking $parking)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'street' => 'sometimes|string',
            'location_number' => 'sometimes|string',
            'zip_code' => 'sometimes|string',
            'city' => 'sometimes|string',
            'country' => 'sometimes|string',
            'total_capacity' => 'sometimes|integer',
            'is_open_24h' => 'sometimes|boolean',
            'opening_hours' => 'nullable|string|required_if:is_open_24h,false',
            'opening_days' => 'nullable|string|required_if:is_open_24h,false|regex:/^([1-7](,[1-7])*)?$/',
            'is_active' => 'sometimes|boolean',
        ]);
        $parking->update($validated);
        return $parking;
    }

    public function destroy(Parking $parking)
    {
        $parking->is_active = false;
        $parking->save();
        return response()->json(['message' => 'Parking soft-deleted (is_active = false)']);
    }
}