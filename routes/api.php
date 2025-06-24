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
|
| Ce fichier définit les points d'entrée de l'API de l'application SmartParking.
| Les routes sont divisées en sections logiques avec des commentaires explicites.
|
*/

/*
|--------------------------------------------------------------------------
| Routes publiques - Authentification
|--------------------------------------------------------------------------
*/
// Création d’un nouvel utilisateur
Route::post('/register', [RegisteredUserController::class, 'store']);

// Connexion utilisateur (génère un token Sanctum)
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Déconnexion utilisateur (invalide le token actuel)
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);


/*
|--------------------------------------------------------------------------
| Routes protégées par Sanctum (nécessitent un token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ✅ Récupère les infos de l'utilisateur connecté
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | Ressources API : Parkings, Parking Spots, Réservations
    |--------------------------------------------------------------------------
    */
    Route::apiResource('/parkings', ParkingController::class);
    Route::apiResource('/parking-spots', ParkingSpotController::class);
    Route::apiResource('/reservations', ReservationController::class);

    /*
    |--------------------------------------------------------------------------
    | Routes administrateur
    |--------------------------------------------------------------------------
    */
    
    // 🔁 Réactiver un utilisateur (admin uniquement)
    Route::patch('/admin/reactivate-user/{user}', [UserController::class, 'reactivate']);
});
    /*
    |--------------------------------------------------------------------------
    | Routes utilisateur : lecture, mise à jour, désactivation, réactivation
    |--------------------------------------------------------------------------
    */

    // 📄 Lire les détails d’un utilisateur spécifique
    Route::get('/user/{user}', [UserController::class, 'show']);

    // ✏️ Mettre à jour un utilisateur (par lui-même ou par un admin)
    Route::put('/user/{user}', [UserController::class, 'update']);

    // 🗑️ Désactiver (soft delete) un utilisateur (par lui-même ou par un admin)
    Route::delete('/user/{user}', [UserController::class, 'destroy']);



/*
|--------------------------------------------------------------------------
| Exemple de versionnement de l’API (v1)
|--------------------------------------------------------------------------
|
| Pour activer, décommentez et placez les routes à l'intérieur du groupe.
|
*/

// Route::prefix('v1')->group(function () {
//     // Exemples de routes versionnées
//     // Route::apiResource('/parkings', ParkingController::class);
//     // Route::apiResource('/parking-spots', ParkingSpotController::class);
//     // Route::apiResource('/reservations', ReservationController::class);
// });