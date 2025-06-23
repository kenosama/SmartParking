<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Affiche toutes les réservations
     */
    public function index()
    {
        return response()->json(
            Reservation::with(['user', 'parkingSpot.parking'])->get()
        );
    }

    /**
     * Crée une nouvelle réservation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'parking_spot_id' => 'required|exists:parking_spots,id',
            'reserved_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'in:active,cancelled_by_user,cancelled_by_owner,done'
        ]);

        $reservation = Reservation::create($validated);

        return response()->json(
            $reservation->load('user', 'parkingSpot.parking'),
            201
        );
    }

    /**
     * Affiche une réservation spécifique
     */
    public function show(Reservation $reservation)
    {
        return response()->json(
            $reservation->load('user', 'parkingSpot.parking')
        );
    }

    /**
     * Met à jour une réservation
     */
    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'parking_spot_id' => 'sometimes|required|exists:parking_spots,id',
            'reserved_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'status' => 'in:active,cancelled_by_user,cancelled_by_owner,done'
        ]);

        $reservation->update($validated);

        return response()->json(
            $reservation->load('user', 'parkingSpot.parking')
        );
    }

    /**
     * Supprime une réservation
     */
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted'], 204);
    }
}
