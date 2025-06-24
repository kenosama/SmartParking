<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\ParkingSpotController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Définition des endpoints de l’API SmartParking.
| Regroupées en sections logiques avec des commentaires clairs.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| 📌 Authentification publique
|--------------------------------------------------------------------------
*/
// Création d’un nouvel utilisateur
Route::post('/register', [RegisteredUserController::class, 'store']);

// Connexion utilisateur (génère un token Sanctum)
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Déconnexion utilisateur (nécessite d’être authentifié)
Route::middleware('auth:sanctum')->post('/logout', [AuthenticatedSessionController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| 🔐 Routes protégées (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // 🔍 Informations de l'utilisateur connecté
    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | 🚗 Ressources principales
    |--------------------------------------------------------------------------
    */
    Route::apiResource('/parkings', ParkingController::class);
    Route::apiResource('/parking-spots', ParkingSpotController::class);
    Route::apiResource('/reservations', ReservationController::class);

    /*
    |--------------------------------------------------------------------------
    | 👤 Gestion des utilisateurs
    |--------------------------------------------------------------------------
    */

    // 📄 Lire les infos d’un utilisateur (par ID ou email)
    Route::get('/user/{identifier}', [UserController::class, 'show']);

    // ✏️ Modifier les infos d’un utilisateur (lui-même ou admin)
    Route::put('/user/{identifier}', [UserController::class, 'update']);

    // 🗑️ Désactiver un utilisateur (soft delete)
    Route::delete('/user/{identifier}', [UserController::class, 'destroy']);

    // ✅ Réactiver un utilisateur désactivé (admin uniquement)
    Route::patch('/admin/reactivate-user/{identifier}', [UserController::class, 'reactivate']);
});

/*
|--------------------------------------------------------------------------
| 📦 Exemple de versionnage d’API
|--------------------------------------------------------------------------
| Pour activer, décommentez ce bloc et déplacez-y vos routes.
|--------------------------------------------------------------------------
*/

// Route::prefix('v1')->group(function () {
//     Route::apiResource('/parkings', ParkingController::class);
//     Route::apiResource('/parking-spots', ParkingSpotController::class);
//     Route::apiResource('/reservations', ReservationController::class);
// });