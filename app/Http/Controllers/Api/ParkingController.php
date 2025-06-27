<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use App\Models\ParkingTransfer;
use App\Models\Parking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
     * Display a listing of parkings.
     *
     * - Admins: see all parkings grouped by creator, with co-owner info.
     * - Non-admins:
     *     - See parkings they created (with co-owners).
     *     - See parkings where they are co-owners (with co-owners).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        if ($user->is_admin) {
            $parkings = Parking::with('user', 'coOwners')->get();

            $grouped = $parkings->groupBy('user_id')->map(function ($userParkings) {
                return [
                    'user' => $this->formatUserInfo($userParkings->first()->user),
                    'parkings' => $userParkings->map(fn ($parking) => $this->formatParkingWithCoOwners($parking)),
                ];
            })->values();

            return response()->json($grouped, 200, [], $jsonFlags);
        }

        $userParkings = $user->parkings()->with('coOwners')->get();
        $coOwnedParkings = $user->coOwnedParkings()->with('coOwners')->get();

        if ($userParkings->isEmpty() && $coOwnedParkings->isEmpty()) {
            return response()->json(['error' => 'Unauthorized. You don\'t have any acces to info here.'], 403);
        }

        return response()->json([
            'user' => $this->formatUserInfo($user),
            'created_parkings' => $userParkings->map(fn ($parking) => $this->formatParkingWithCoOwners($parking)),
            'co_owned_parkings' => $coOwnedParkings->map(fn ($parking) => $this->formatParkingWithCoOwners($parking)),
        ], 200, [], $jsonFlags);
    }

    /**
     * Store a new parking entry in the database.
     *
     * Access restricted to:
     * - Admins
     * - Active owners (is_owner = true, is_active = true)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\App\Models\Parking
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check: Only admins or active owners can create a parking
        if (! $user->is_admin && (! $user->is_owner || ! $user->is_active)) {
            return response()->json(['error' => 'Unauthorized. only admins or active owners can create a parking'], 403);
        }

        // Input processing: handle opening days/hours normalization
        $this->processOpeningDaysAndHours($request);

        // Input validation
        $validated = $request->validate($this->validationRules());

        // Processing: assign creator and set active by default
        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;

        // Eloquent operation: create parking
        $parking = Parking::create($validated);

        $parking->coOwners()->attach($user->id, ['role' => 'co_owner']);

        // Response: return the created parking
        return $parking;
    }

    /**
     * Display details of a specific parking.
     *
     * Access allowed for:
     * - Admins
     * - Creator of the parking
     * - Co-owners of the parking
     *
     * @param \App\Models\Parking $parking
     * @return \Illuminate\Http\JsonResponse|\App\Models\Parking
     */
    public function show(Parking $parking)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check: Only admins, the creator, or co-owners can view details
        if (
            ! $user->is_admin &&
            $parking->user_id !== $user->id &&
            ! $parking->coOwners()->where('user_id', $user->id)->exists()
        ) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Eloquent operation: Load related coOwners
        $parking->load('coOwners');

        // Response: return formatted parking with co-owners (same as index)
        return response()->json($this->formatParkingWithCoOwners($parking));
    }

    /**
     * Update a specific parking.
     *
     * Only admins and the creator of the parking are allowed to update.
     * - If is_active is set to false: disables the parking and all its spots.
     * - If is_active is set to true: re-enables spots depending on user's role.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Parking $parking
     * @return \Illuminate\Http\JsonResponse|\App\Models\Parking
     */
    public function update(Request $request, Parking $parking)
    {
        // Input processing: normalize opening days/hours if present
        $this->processOpeningDaysAndHours($request);

        // Input validation for partial update
        $validated = $request->validate($this->validationRules(true));

        if (isset($validated['user_email'])) {
            $newUser = User::where('email', $validated['user_email'])->first();

            if ($newUser && $parking->user_id !== $newUser->id) {
                DB::transaction(function () use ($parking, $newUser) {
                    $previousUserId = $parking->user_id;
                    $parking->user_id = $newUser->id;
                    $parking->save();

                    ParkingTransfer::create([
                        'parking_id'   => $parking->id,
                        'old_user_id'  => $previousUserId,
                        'new_user_id'  => $newUser->id,
                        'performed_by' => Auth::id(),
                    ]);

                    if (! $parking->coOwners->contains($newUser->id)) {
                        $parking->coOwners()->attach($newUser->id, ['role' => 'co_owner']);
                    }

                    // Replace references in pivot only if they exist
                    $pivotExists = DB::table('parking_owner')
                        ->where('parking_id', $parking->id)
                        ->where('user_id', $previousUserId)
                        ->exists();

                    if ($pivotExists) {
                        DB::table('parking_owner')
                            ->where('parking_id', $parking->id)
                            ->where('user_id', $previousUserId)
                            ->update(['user_id' => $newUser->id]);
                    }
                });
            }
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check: Only admins or the creator can update
        if (! $user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Eloquent operation: update model attributes
        $parking->update($validated);

        // Conditional business logic: handle activation/deactivation
        if (isset($validated['is_active']) && $validated['is_active'] === false) {
            // If disabling parking, also disable all spots
            $this->deactivateParkingAndSpots($parking);
        }
        elseif (isset($validated['is_active']) && $validated['is_active'] === true) {
            // If enabling parking, enable all spots (admin or creator only)
            if ($user->is_admin || $parking->user_id === $user->id) {
                $parking->spots()->update(['is_available' => true]);
            }
        }

        // Response: return updated parking
        return $parking;
    }

    /**
     * Soft-delete a parking by setting is_active to false.
     *
     * Access allowed for:
     * - Admins
     * - Creator of the parking
     *
     * Also disables all linked parking spots.
     *
     * @param \App\Models\Parking $parking
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Parking $parking)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check: Only admin or creator can soft-delete
        if (! $user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Business logic: deactivate parking and all its spots
        $this->deactivateParkingAndSpots($parking);

        // Response: confirmation message
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
            $rules['user_email'] = 'sometimes|email|exists:users,email';
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

    /**
     * Format user basic identity.
     */
    private function formatUserInfo(\App\Models\User $user): array
    {
        return [
            'full_name' => trim($user->first_name . ' ' . $user->last_name),
            'email' => $user->email,
        ];
    }

    /**
     * Format a parking and its co-owners for output.
     */
    private function formatParkingWithCoOwners(Parking $parking): array
    {
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
            'is_active' => $parking->is_active,
            'co_owners' => $parking->coOwners->map(fn ($owner) => [
                'full_name' => trim($owner->first_name . ' ' . $owner->last_name),
                'email' => $owner->email,
            ]),
        ];
    }
}