<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        // Récupération des identifiants de connexion (email et mot de passe)
        $credentials = $request->only('email', 'password');

        // Validation des champs requis
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Tentative d'authentification avec les identifiants
        if (!Auth::attempt($credentials)) {
            // Si la tentative échoue, on retourne une réponse adaptée selon si la requête attend du JSON ou non
            return $request->expectsJson()
                ? response()->json(['message' => 'Invalid login details'], 401)
                : back()->withErrors(['email' => 'Invalid login details'])->onlyInput('email');
        }

        // Authentification réussie, récupération de l'utilisateur connecté
        $user = Auth::user();

        // Si la requête est une requête API (JSON ou Bearer token)
        if ($request->expectsJson() || $request->bearerToken()) {
            // On retourne un token d'authentification et les infos de l'utilisateur
            return response()->json([
                'token' => $user->createToken('api-token')->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'is_owner' => $user->is_owner,
                    'is_tenant' => $user->is_tenant,
                    'is_admin' => $user->is_admin,
                ],
            ]);
        }

        // Sinon, on régénère la session pour sécuriser l'authentification web
        $request->session()->regenerate();

        // Redirection vers la page d’accueil prévue après connexion
        return redirect()->intended('/dashboard');
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        // Si la requête est API (JSON ou avec un token Bearer)
        if ($request->expectsJson() || $request->bearerToken()) {
            // On vérifie que l'utilisateur a bien un token d'API actif, puis on le révoque
            /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
            $token = $request->user()?->currentAccessToken();
            $token?->delete();

            // On retourne une confirmation de déconnexion API
            return response()->json(['message' => 'Logged out']);
        }

        // Sinon, pour les sessions web classiques :
        Auth::guard('web')->logout();
        // Invalidation de la session
        $request->session()->invalidate();
        // Régénération du token CSRF
        $request->session()->regenerateToken();

        // Redirection vers la page de connexion après déconnexion
        return redirect('/login');
    }
}
