<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Retrieve the user model from ID or email.
     */
    private function getUserModel(string $user): User
    {
        return is_numeric($user)
            ? User::findOrFail($user)
            : User::where('email', $user)->firstOrFail();
    }

    /**
     * Show a specific user's information (admin or self).
     */
    public function show(string $user): JsonResponse
    {
        // Retrieve the user model by ID or email
        $userModel = $this->getUserModel($user);

        // Allow only the user themselves or an admin to view
        if (Auth::id() !== $userModel->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Return the user data as JSON
        return response()->json($userModel);
    }

    /**
     * Update user information (admin or self).
     */
    public function update(Request $request, string $user): JsonResponse
    {
        // Retrieve the user model by ID or email
        $userModel = $this->getUserModel($user);

        // Only allow update if current user is self or admin
        if (Auth::id() !== $userModel->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate fields if present in the request
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $userModel->id],
            'password' => ['sometimes', 'confirmed', Rules\Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Hash the password if it's being updated
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Update the user with validated data
        $userModel->update($validated);

        // Return success message with updated user data
        return response()->json(['message' => 'User updated successfully.', 'user' => $userModel]);
    }

    /**
     * Soft-delete a user by setting is_active to false.
     */
    public function destroy(string $user): JsonResponse
    {
        // Retrieve the user model by ID or email
        $userModel = $this->getUserModel($user);

        // Only allow deletion if current user is self or admin
        if (Auth::id() !== $userModel->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent one admin from deactivating another unless it's self
        if ($userModel->is_admin && Auth::id() !== $userModel->id) {
            return response()->json(['error' => 'Cannot deactivate another admin'], 403);
        }

        // Perform soft delete by deactivating user
        $userModel->is_active = false;
        $userModel->save();

        // Return success message
        return response()->json(['message' => 'User deactivated (soft delete).']);
    }

    /**
     * Allows an admin to reactivate a deactivated user.
     */
    public function reactivate(string $user): JsonResponse
    {
        // Retrieve the user model by ID or email
        $userModel = $this->getUserModel($user);

        // Only admins can reactivate users
        if (!Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Reactivate the user
        $userModel->is_active = true;
        $userModel->save();

        // Return success message
        return response()->json(['message' => 'User reactivated successfully.']);
    }
}
