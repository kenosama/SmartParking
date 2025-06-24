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
        // Apply Sanctum authentication middleware to all controller methods
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of all parkings including their owner and associated parking spots.
     */
    public function index()
    {
        return Parking::with('user', 'parkingSpots')->get();
    }

    /**
     * Store a newly created parking in the database.
     */
    public function store(Request $request)
    {
        // Normalize opening_days input format if present
        $this->normalizeOpeningDays($request);

        // Validate request inputs
        $validated = $request->validate($this->validationRules());

        // Assign current authenticated user and default active status
        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;

        // Create and return new parking record
        return Parking::create($validated);
    }

    /**
     * Display the specified parking with its related user and parking spots.
     */
    public function show(Parking $parking)
    {
        return $parking->load('user', 'parkingSpots');
    }

    /**
     * Update the specified parking in the database.
     */
    public function update(Request $request, Parking $parking)
    {
        // Normalize opening_days input format if present
        $this->normalizeOpeningDays($request);

        // Validate input for partial update
        $validated = $request->validate($this->validationRules(true));

        // Apply updates to the parking model
        $parking->update($validated);
        return $parking;
    }

    /**
     * Soft delete the specified parking by setting is_active to false.
     */
    public function destroy(Parking $parking)
    {
        $parking->is_active = false;
        $parking->save();

        return response()->json(['message' => 'Parking soft-deleted (is_active = false)']);
    }

    /**
     * Normalize the opening_days string to use commas instead of hyphens.
     */
    private function normalizeOpeningDays(Request $request): void
    {
        if ($request->has('opening_days')) {
            $request->merge([
                'opening_days' => str_replace('-', ',', $request->input('opening_days')),
            ]);
        }
    }

    /**
     * Define validation rules for parking creation and update.
     */
    private function validationRules(bool $isUpdate = false): array
    {
        $rules = [
            'name' => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'street' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'location_number' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'zip_code' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'city' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'country' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'total_capacity' => ($isUpdate ? 'sometimes' : 'required') . '|integer',
            'is_open_24h' => ($isUpdate ? 'sometimes' : 'required') . '|boolean',
            'opening_hours' => 'nullable|string|required_if:is_open_24h,false',
            'opening_days' => 'nullable|string|required_if:is_open_24h,false|regex:/^([1-7](,[1-7])*)?$/',
        ];

        if ($isUpdate) {
            $rules['is_active'] = 'sometimes|boolean';
        }

        return $rules;
    }
}