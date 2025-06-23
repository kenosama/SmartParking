<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Affiche les infos d'un utilisateur spécifique (admin ou lui-même).
     */
    public function show(User $user): JsonResponse
    {
        if (Auth::id() !== $user->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($user);
    }

    /**
     * Met à jour les infos de l'utilisateur (admin ou lui-même).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (Auth::id() !== $user->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'confirmed', Rules\Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'User updated successfully.', 'user' => $user]);
    }

    /**
     * Désactive un utilisateur (soft delete logique).
     */
    public function destroy(User $user): JsonResponse
    {
        // Vérifie si l'utilisateur courant est autorisé (lui-même ou un admin)
        if (Auth::id() !== $user->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Empêche la désactivation d'un autre admin (sauf lui-même)
        if ($user->is_admin && Auth::id() !== $user->id) {
            return response()->json(['error' => 'Cannot deactivate another admin'], 403);
        }

        // Soft delete via is_active
        $user->is_active = false;
        $user->save();

        return response()->json(['message' => 'User deactivated (soft delete).']);
    }
    /**
     * permet a un admin de re-activer un user
     */
    public function reactivate(User $user): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user->is_active = true;
        $user->save();

        return response()->json(['message' => 'User reactivated successfully.']);
    }
}
