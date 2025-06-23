<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\ParkingSpotController;
use App\Http\Controllers\Api\ReservationController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// 💡 Astuce : pour préparer une future évolution, vous pouvez regrouper ces routes sous un préfixe versionné comme 'v1'
// Example :
// Route::prefix('v1')->group(function () {
//     Route::middleware('auth:sanctum')->apiResource('parkings', ParkingController::class);
//     ...
// });

Route::middleware('auth:sanctum')->apiResource('parkings', ParkingController::class);
Route::middleware('auth:sanctum')->apiResource('reservations', ReservationController::class);
Route::middleware('auth:sanctum')->apiResource('parking-spots', ParkingSpotController::class);


Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);