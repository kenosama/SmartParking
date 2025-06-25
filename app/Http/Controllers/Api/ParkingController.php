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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->is_admin) {
            // All parking regrouped by user.
            $parkings = Parking::with('user', 'spots')->get();

            $grouped = $parkings->groupBy('user_id')->map(function ($userParkings) {
                return [
                    'user' => $userParkings->first()->user,
                    'parkings' => $userParkings->map(function ($parking) {
                        return [
                            'id' => $parking->id,
                            'name' => $parking->name,
                            'street' => $parking->street,
                            'location_number' => $parking->location_number,
                            'zip_code' => $parking->zip_code,
                            'city' => $parking->city,
                            'country' => $parking->country,
                            'total_capacity' => $parking->total_capacity,
                            'is_open_24h' => $parking->is_open_24h,
                            'opening_hours' => $parking->opening_hours,
                            'opening_days' => $parking->opening_days,
                            'spots' => $parking->spots,
                        ];
                    }),
                ];
            })->values();

            $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT; // Commenter JSON_PRETTY_PRINT en production
            return response()->json($grouped, 200, [], $jsonFlags);
        } else {
            // Parkings of current user
            $userParkings = $user->parkings()->with('spots')->get();

            $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT; // Commenter JSON_PRETTY_PRINT en production
            return response()->json([
                'user' => $user,
                'parkings' => $userParkings->map(function ($parking) {
                    return [
                        'id' => $parking->id,
                        'name' => $parking->name,
                        'street' => $parking->street,
                        'location_number' => $parking->location_number,
                        'zip_code' => $parking->zip_code,
                        'city' => $parking->city,
                        'country' => $parking->country,
                        'total_capacity' => $parking->total_capacity,
                        'is_open_24h' => $parking->is_open_24h,
                        'opening_hours' => $parking->opening_hours,
                        'opening_days' => $parking->opening_days,
                        'spots' => $parking->spots,
                    ];
                }),
            ], 200, [], $jsonFlags);
        }
    }

    /**
     * Store a newly created parking in the database.
     */
    public function store(Request $request)
    {
        // Process opening days and hours (handle ranges and 24h logic)
        $this->processOpeningDaysAndHours($request);

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
        return $parking->load('user', 'spots');
    }

    /**
     * Update the specified parking in the database.
     */
    public function update(Request $request, Parking $parking)
    {
        // Process opening days and hours (handle ranges and 24h logic)
        $this->processOpeningDaysAndHours($request);

        // Validate input for partial update
        $validated = $request->validate($this->validationRules(true));

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Seuls les admins ou les propriétaires peuvent modifier ce parking
        if (! $user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Apply updates to the parking model
        $parking->update($validated);
        if (isset($validated['is_active']) && $validated['is_active'] === false) {
            $this->deactivateParkingAndSpots($parking);
        }
        elseif (isset($validated['is_active']) && $validated['is_active'] === true) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->is_admin) {
                $parking->spots()->update(['is_available' => true]);
            } elseif ($parking->user_id === $user->id) {
                $parking->spots()->update(['is_available' => true]);
            }
        }
        return $parking;
    }

    /**
     * Soft delete the specified parking by setting is_active to false.
     */
    public function destroy(Parking $parking)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only admin or owners can softdelete the parking.
        if (! $user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $this->deactivateParkingAndSpots($parking);

        return response()->json(['message' => 'Parking soft-deleted (is_active = false)']);
    }

    /**
     * Process and normalize the opening_days and opening_hours input.
     * - If is_open_24h is true, set opening_hours to "00:00-23:59" and opening_days to "1,2,3,4,5,6,7".
     * - Otherwise, expand any range notation like "1-5" to "1,2,3,4,5" in opening_days.
     *
     * @param Request $request
     * @return void
     */
    private function processOpeningDaysAndHours(Request $request): void
    {
        if (!$request->has('opening_days') && !$request->has('opening_hours') && !$request->has('is_open_24h')) {
            return;
        }

        $isOpen24h = $request->input('is_open_24h');

        if ($isOpen24h) {
            // If open 24h, force hours and days
            $request->merge([
                'opening_hours' => '00:00-23:59',
                'opening_days' => '1,2,3,4,5,6,7',
            ]);
            return;
        }

        // If opening_days is set and contains a range like "1-5"
        if ($request->has('opening_days')) {
            $days = $request->input('opening_days');

            // Check if days contains a hyphen (range)
            if (strpos($days, '-') !== false) {
                [$start, $end] = explode('-', $days, 2);

                // Validate start and end as integers between 1 and 7
                $start = intval($start);
                $end = intval($end);

                if ($start > 0 && $end > 0 && $start <= $end && $start <= 7 && $end <= 7) {
                    // Build expanded list of days
                    $expanded = [];
                    for ($i = $start; $i <= $end; $i++) {
                        $expanded[] = $i;
                    }
                    $request->merge(['opening_days' => implode(',', $expanded)]);
                }
            }
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

    private function deactivateParkingAndSpots(Parking $parking): void
    {
        $parking->is_active = false;
        $parking->save();

        // Rendre tous les spots liés indisponibles
        $parking->spots()->update(['is_available' => false]);
    }
}