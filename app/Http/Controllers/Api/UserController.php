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
     * Affiche les infos d'un utilisateur spécifique (admin ou lui-même).
     */
    public function show(string $user): JsonResponse
    {
        $userModel = is_numeric($user)
            ? User::findOrFail($user)
            : User::where('email', $user)->firstOrFail();

        if (Auth::id() !== $userModel->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($userModel);
    }

    /**
     * Met à jour les infos de l'utilisateur (admin ou lui-même).
     */
    public function update(Request $request, string $user): JsonResponse
    {
        $userModel = is_numeric($user)
            ? User::findOrFail($user)
            : User::where('email', $user)->firstOrFail();

        if (Auth::id() !== $userModel->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $userModel->id],
            'password' => ['sometimes', 'confirmed', Rules\Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $userModel->update($validated);

        return response()->json(['message' => 'User updated successfully.', 'user' => $userModel]);
    }

    /**
     * Désactive un utilisateur (soft delete logique).
     */
    public function destroy(string $user): JsonResponse
    {
        $userModel = is_numeric($user)
            ? User::findOrFail($user)
            : User::where('email', $user)->firstOrFail();

        // Vérifie si l'utilisateur courant est autorisé (lui-même ou un admin)
        if (Auth::id() !== $userModel->id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Empêche la désactivation d'un autre admin (sauf lui-même)
        if ($userModel->is_admin && Auth::id() !== $userModel->id) {
            return response()->json(['error' => 'Cannot deactivate another admin'], 403);
        }

        // Soft delete via is_active
        $userModel->is_active = false;
        $userModel->save();

        return response()->json(['message' => 'User deactivated (soft delete).']);
    }
    /**
     * permet a un admin de re-activer un user
     */
    public function reactivate(string $user): JsonResponse
    {
        $userModel = is_numeric($user)
            ? User::findOrFail($user)
            : User::where('email', $user)->firstOrFail();

        if (!Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userModel->is_active = true;
        $userModel->save();

        return response()->json(['message' => 'User reactivated successfully.']);
    }
}
