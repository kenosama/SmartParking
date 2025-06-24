<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
     * Crée une nouvelle ou plusieurs réservations
     */
    public function store(Request $request)
    {
        // ✅ Valide les données de la requête : utilisateur, parking, créneaux horaires, identifiants d'emplacements, plaques...
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'parking_id' => 'required|exists:parkings,id',
            'parking_spot_identifiers' => 'required|string', // comma-separated identifiers
            'reserved_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'in:active,cancelled_by_user,cancelled_by_owner,done',
            'license_plate' => 'required|string' // comma-separated plates
        ]);

        // 🔁 Sépare les identifiants par virgule, pour supporter A1,A2,B1-B3,B5 etc.
        $rawIdentifiers = array_map('trim', explode(',', $validated['parking_spot_identifiers']));
        $identifiers = [];

        // 🔎 Détecte les plages de type "A1-A5" et les transforme en liste [A1, A2, ..., A5]
        foreach ($rawIdentifiers as $entry) {
            if (preg_match('/^([A-Z]+)(\d+)-([A-Z]+)?(\d+)$/i', $entry, $matches)) {
                $prefixStart = strtoupper($matches[1]);
                $startNum = (int)$matches[2];
                $prefixEnd = $matches[3] ? strtoupper($matches[3]) : $prefixStart;
                $endNum = (int)$matches[4];

                if ($prefixStart !== $prefixEnd) {
                    continue; // ignorer les plages croisées de lettres
                }

                for ($i = $startNum; $i <= $endNum; $i++) {
                    $identifiers[] = $prefixStart . $i;
                }
            } else {
                $identifiers[] = strtoupper($entry);
            }
        }

        // 🚘 Nettoie les plaques d'immatriculation (supprime espaces/symboles et met en majuscules)
        $plates = array_map(function ($p) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $p));
        }, explode(',', $validated['license_plate']));

        // 🛑 Vérifie que chaque emplacement a une plaque d'immatriculation correspondante
        if (count($identifiers) !== count($plates)) {
            return response()->json([
                'error' => 'Number of license plates must match number of parking spots.'
            ], 422);
        }

        // 📍 Récupère les emplacements valides correspondant aux identifiants donnés dans ce parking
        $parkingSpots = \App\Models\ParkingSpot::where('parking_id', $validated['parking_id'])
            ->whereIn('identifier', $identifiers)
            ->get();

        if (count($parkingSpots) !== count($identifiers)) {
            return response()->json([
                'error' => 'One or more parking spot identifiers are invalid for the selected parking.'
            ], 422);
        }

        $reservations = [];

        foreach ($parkingSpots as $index => $spot) {
            // ⚠️ Vérifie les conflits de réservation pour chaque emplacement demandé
            $conflict = \App\Models\Reservation::where('parking_spot_id', $spot->id)
                ->where('reserved_date', $validated['reserved_date'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhere(function ($query2) use ($validated) {
                              $query2->where('start_time', '<=', $validated['start_time'])
                                     ->where('end_time', '>=', $validated['end_time']);
                          });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'error' => "Spot {$spot->identifier} is already reserved for the selected time."
                ], 409);
            }

            // ✅ Crée la réservation si aucune collision n'est détectée
            $reservation = \App\Models\Reservation::create([
                'user_id' => $validated['user_id'],
                'parking_spot_id' => $spot->id,
                'reserved_date' => $validated['reserved_date'],
                'end_date' => $validated['end_date'] ?? null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'status' => $validated['status'] ?? 'active',
                'license_plate' => $plates[$index],
            ]);

            $reservations[] = $reservation->load('user', 'parkingSpot.parking');
        }

        // 📦 Retourne toutes les réservations créées avec les relations utilisateur et parking chargées
        return response()->json($reservations, 201);
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
     * Désactive (annule) une réservation sans la supprimer de la base (soft delete logique via status)
     */
    public function destroy(Reservation $reservation)
    {
        // Si la réservation est déjà annulée ou terminée, inutile de la modifier
        if (in_array($reservation->status, ['cancelled_by_user', 'cancelled_by_owner', 'done'])) {
            return response()->json([
                'message' => 'Reservation is already finalized or cancelled.'
            ], 200);
        }

        // Récupère l'ID du propriétaire du parking lié à la réservation
        $spotOwnerId = $reservation->parkingSpot->parking->user_id ?? null;

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // ❌ Empêche un admin de désactiver une réservation s’il n’est pas autorisé (double sécurité)
        if ($currentUser->is_admin && $currentUser->id !== $reservation->user_id && $currentUser->id !== ($reservation->parkingSpot->parking->user_id ?? null)) {
            // Admins peuvent annuler à tout moment, donc cette vérification est purement informative si on veut l'utiliser plus tard
            // return response()->json(['error' => 'Admin override non autorisé'], 403);
        }

        // ⚠️ Admin peut tout faire, saute les vérifs
        if ($currentUser->is_admin) {
            $reservation->status = 'cancelled_by_admin';
            $reservation->save();

            return response()->json([
                'message' => 'Reservation cancelled by admin.',
                'status' => $reservation->status
            ], 200);
        }

        if ($currentUser->id === $spotOwnerId) {
            $now = now();
            $reservationDateTime = Carbon::parse($reservation->reserved_date . ' ' . $reservation->start_time);

            if ($reservationDateTime->diffInHours($now) <= 48) {
                $reservation->status = 'cancelled_by_owner';
                $reservation->save();

                return response()->json([
                    'message' => 'Reservation cancelled by parking owner within 48h before start.',
                    'status' => $reservation->status
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Parking owner can only cancel within 48h before the reservation start time.'
                ], 403);
            }
        }

        // On identifie qui annule : user, admin ou autre logique
        if ($currentUser->id === $reservation->user_id) {
            $now = now();
            $reservationDateTime = Carbon::parse($reservation->reserved_date . ' ' . $reservation->start_time);

            // ❗ L'utilisateur peut annuler uniquement si on est à +24h ou plus du début
            if ($reservationDateTime->diffInHours($now) >= 24) {
                $reservation->status = 'cancelled_by_user';
            } else {
                return response()->json(['error' => 'You can only cancel your reservation at least 24h in advance.'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized to cancel this reservation.'], 403);
        }

        $reservation->save();

        return response()->json(['message' => 'Reservation cancelled successfully.', 'status' => $reservation->status], 200);
    }
}
