<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParkingOwnerController extends Controller
{
    public function store(Request $request, Parking $parking)
    {
        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'email|exists:users,email',
        ]);

        $user = Auth::user();

        // Only the creator of the parking or an admin can add co-owners
        if (!$user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $users = User::whereIn('email', $request->emails)->get();

        foreach ($users as $coOwner) {
            $parking->coOwners()->syncWithoutDetaching([
                $coOwner->id => ['role' => 'co_owner']
            ]);
        }

        return response()->json(['message' => 'Co-owners added successfully.']);
    }

    public function destroy(Request $request, Parking $parking)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = Auth::user();

        // Only the creator of the parking or an admin can remove co-owners
        if (!$user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $coOwner = User::where('email', $request->email)->first();

        $parking->coOwners()->detach($coOwner->id);

        return response()->json(['message' => 'Co-owner removed successfully.']);
    }

    public function index(Parking $parking)
    {
        $user = Auth::user();

        // Only the creator of the parking or an admin can view co-owners
        if (!$user->is_admin && $parking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $coOwners = $parking->coOwners()->get(['users.id', 'users.first_name', 'users.last_name', 'users.email']);

        return response()->json(['co_owners' => $coOwners]);
    }
}

