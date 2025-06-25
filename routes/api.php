<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\ParkingSpotController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ParkingOwnerController;

/*
|--------------------------------------------------------------------------
| API Routes - SmartParking
|--------------------------------------------------------------------------
| This file defines the API endpoints for the SmartParking application.
| Routes are grouped into logical sections for public and authenticated access.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| 📌 Public Routes
| Routes accessible without authentication.
|--------------------------------------------------------------------------
| 📌 Routes publiques
| Routes accessibles sans authentification.
|--------------------------------------------------------------------------
*/

// Register a new user
Route::post('/register', [RegisteredUserController::class, 'store']); // 👤 Register user

// Authenticate user and issue Sanctum token
Route::post('/login', [AuthenticatedSessionController::class, 'store']); // 🔐 Login

// Logout (requires authentication)
Route::middleware('auth:sanctum')->post('/logout', [AuthenticatedSessionController::class, 'destroy']); // 🚪 Logout

// Public parking spot search (e.g., by country, city, postal code)
Route::get('/parking-spots/search', [ParkingSpotController::class, 'search']); // 🔍 Spot search

/*
|--------------------------------------------------------------------------
| 🔐 Protected Routes (auth:sanctum)
| Routes that require authentication.
|--------------------------------------------------------------------------
| 🔐 Routes protégées (auth:sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Return authenticated user info
    Route::get('/me', function (Request $request) {
        return $request->user(); // 🙋‍♂️ Connected user info
    });

    /*
    |--------------------------------------------------------------------------
    | 🚗 Main Resources
    | API resource controllers for parkings, spots, and reservations
    |--------------------------------------------------------------------------
    | 🚗 Ressources principales
    |--------------------------------------------------------------------------
    */
    Route::apiResource('/parkings', ParkingController::class);            // 🅿️ Parking
    Route::apiResource('/parking-spots', ParkingSpotController::class);   // 🅿️ Parking spots
    Route::apiResource('/reservations', ReservationController::class);    // 📅 Reservations

    // 👥 Co-owners management
    Route::get('/parkings/{parking}/co-owners', [ParkingOwnerController::class, 'index']); // 👥 List co-owners
    Route::post('/parkings/{parking}/co-owners', [ParkingOwnerController::class, 'store']); // ➕ Add co-owners
    Route::delete('/parkings/{parking}/co-owners', [ParkingOwnerController::class, 'destroy']); // ❌ Remove co-owner

    /*
    |--------------------------------------------------------------------------
    | 👤 User Management
    | Manage user data and status
    |--------------------------------------------------------------------------
    | 👤 Gestion des utilisateurs
    |--------------------------------------------------------------------------
    */

    // Get user info by ID or email
    Route::get('/user/{identifier}', [UserController::class, 'show']); // 🔍 Show user

    // Update user info (self or admin)
    Route::put('/user/{identifier}', [UserController::class, 'update']); // ✏️ Update user

    // Deactivate a user (soft delete)
    Route::delete('/user/{identifier}', [UserController::class, 'destroy']); // 🗑️ Deactivate user

    // Reactivate a deactivated user (admin only)
    Route::patch('user/{identifier}/reactivate', [UserController::class, 'reactivate']); // ✅ Reactivate user
});

/*
|--------------------------------------------------------------------------
| 📦 API Versioning Example
| Uncomment to enable version-specific routing.
|--------------------------------------------------------------------------
| 📦 Exemple de versionnage d’API
|--------------------------------------------------------------------------
*/

// Route::prefix('v1')->group(function () {
//     Route::apiResource('/parkings', ParkingController::class);
//     Route::apiResource('/parking-spots', ParkingSpotController::class);
//     Route::apiResource('/reservations', ReservationController::class);
// });