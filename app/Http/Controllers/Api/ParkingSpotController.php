<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParkingSpotController extends Controller
{
    /**
     * Liste tous les spots de l'utilisateur connecté.
     */
    public function index()
    {
        // 🔍 Récupère uniquement les spots créés par l'utilisateur connecté.
        // Chaque place est reliée à un parking (relation belongsTo).
        $spots = ParkingSpot::where('user_id', Auth::id())->with('parking')->get();
        return response()->json($spots);
    }

    /**
     * Crée une nouvelle place de parking.
     */
    public function store(Request $request)
    {
        // ✅ Valide les données envoyées pour créer une place de parking.
        // Ces champs sont requis pour définir une place dans un parking existant.
        $validated = $request->validate([
            'identifier' => 'required|string|max:10',
            'parking_id' => 'required|exists:parkings,id',
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        // 🔗 Récupère le parking associé pour vérifier les contraintes liées à la capacité.
        $parking = \App\Models\Parking::findOrFail($validated['parking_id']);

        // ⚠️ Vérifie si le nombre total de places existantes dépasse la capacité du parking.
        // La relation parking->spots est définie dans le modèle Parking (hasMany).
        $existingSpotsCount = $parking->spots()->count();
        if ($existingSpotsCount >= $parking->capacity) {
            return response()->json([
                'error' => 'Ce parking a déjà atteint sa capacité maximale de ' . $parking->capacity . ' places.'
            ], 400);
        }

        // ❌ Vérifie s’il existe déjà une place avec le même identifiant (ex: A1) dans le même parking.
        // Cela garantit l’unicité des identifiants dans un parking donné.
        $duplicate = $parking->spots()
            ->where('identifier', $validated['identifier'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'error' => 'Une place avec cet identifiant existe déjà dans ce parking.'
            ], 409);
        }

        // 🏷️ Crée une nouvelle place avec les infos validées, liée à l'utilisateur connecté.
        // La relation user->parking_spots est implicite via user_id.
        $spot = ParkingSpot::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return response()->json($spot, 201);
    }

    /**
     * Affiche une seule place de parking.
     */
    public function show(ParkingSpot $parkingSpot)
    {
        // 🔍 Affiche les détails de la place de parking, avec les relations vers le parking et le propriétaire.
        // La méthode load() charge les relations définies dans le modèle ParkingSpot.
        return response()->json([
            'spot' => $parkingSpot->load('parking', 'user'),
            'proprietaire' => $parkingSpot->user->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * Met à jour une place de parking.
     */
    public function update(Request $request, ParkingSpot $parkingSpot)
    {
        // 🔒 Seul le propriétaire de la place ou un administrateur peut modifier une place.
        if ($parkingSpot->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        // ✅ Valide les modifications possibles sur une place existante.
        $validated = $request->validate([
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $parkingSpot->update($validated);

        return response()->json($parkingSpot);
    }

    /**
     * Supprime une place de parking.
     */
    public function destroy(ParkingSpot $parkingSpot)
    {
        // 🔒 Seul le propriétaire de la place ou un admin peut supprimer une place.
        if ($parkingSpot->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        // 🗑️ Supprime la place de la base de données.
        $parkingSpot->delete();

        return response()->json(['message' => 'Place supprimée.']);
    }
}
