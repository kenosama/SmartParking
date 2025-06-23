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
        // ✅ Valide les données envoyées.
        $validated = $request->validate([
            'identifiers' => 'required|string', // Ex: "A1-A5,B1,B2-B3"
            'parking_id' => 'required|exists:parkings,id',
            'allow_electric_charge' => 'boolean',
            'is_available' => 'boolean',
            'per_day_only' => 'boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $parking = \App\Models\Parking::findOrFail($validated['parking_id']);
        $existingCount = $parking->spots()->count();

        // 📦 Gère les identifiants multiples et les plages (ex: A1-A5, B1-B2, C3)
        $input = $request->input('identifiers');
        $identifiers = collect(explode(',', $input))
            ->flatMap(function ($item) {
                $item = trim($item);
                if (preg_match('/^([A-Z])(\d+)-([A-Z])(\d+)$/i', $item, $m) && $m[1] === $m[3]) {
                    // Plage même lettre : A1-A5
                    $prefix = strtoupper($m[1]);
                    $start = intval($m[2]);
                    $end = intval($m[4]);
                    return collect(range($start, $end))->map(fn($n) => $prefix . $n);
                } elseif (preg_match('/^(\d+)-(\d+)$/', $item, $m)) {
                    // Plage numérique : 101-105
                    return collect(range(intval($m[1]), intval($m[2])));
                } else {
                    return [$item];
                }
            })
            ->map(fn($id) => strtoupper(trim($id)))
            ->unique();

        // 🚨 Vérifie si ça dépasse la capacité
        if ($existingCount + $identifiers->count() > $parking->total_capacity) {
            return response()->json(['error' => 'Trop de places par rapport à la capacité.'], 400);
        }

        // 🎯 Vérifie les doublons dans la DB
        $existing = $parking->spots()
            ->whereIn('identifier', $identifiers)
            ->pluck('identifier')
            ->map(fn($v) => strtoupper($v))
            ->toArray();

        if (!empty($existing)) {
            return response()->json(['error' => 'Les identifiants suivants existent déjà : ' . implode(', ', $existing)], 409);
        }

        // 🧱 Crée les nouvelles places
        $created = [];
        foreach ($identifiers as $identifier) {
            $spot = ParkingSpot::create([
                'identifier' => $identifier,
                'parking_id' => $validated['parking_id'],
                'user_id' => Auth::id(),
                'allow_electric_charge' => $request->boolean('allow_electric_charge', false),
                'is_available' => $request->boolean('is_available', true),
                'per_day_only' => $request->boolean('per_day_only', false),
                'price_per_day' => $validated['price_per_day'] ?? null,
                'price_per_hour' => $validated['price_per_hour'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
            $spot->load(['parking', 'user']);
            $created[] = [
                'user' => [
                    'full_name' => $spot->user->first_name . ' ' . $spot->user->last_name,
                    'email' => $spot->user->email,
                ],
                'parking' => [
                    'id' => $spot->parking->id,
                    'name' => $spot->parking->name,
                    'street' => $spot->parking->street,
                    'location_number' => $spot->parking->location_number,
                    'zip_code' => $spot->parking->zip_code,
                    'city' => $spot->parking->city,
                    'country' => $spot->parking->country,
                    'total_capacity' => $spot->parking->total_capacity,
                    'is_open_24h' => $spot->parking->is_open_24h,
                    'opening_hours' => $spot->parking->opening_hours,
                    'opening_days' => $spot->parking->opening_days,
                ],
                'spot' => [
                    'id' => $spot->id,
                    'identifier' => $spot->identifier,
                    'allow_electric_charge' => $spot->allow_electric_charge,
                    'is_available' => $spot->is_available,
                    'per_day_only' => $spot->per_day_only,
                    'price_per_day' => $spot->price_per_day,
                    'price_per_hour' => $spot->price_per_hour,
                ]
            ];
        }

        return response()->json([
            'parking' => $created[0]['parking'],
            'user' => $created[0]['user'],
            'spots' => collect($created)->pluck('spot'),
            'count' => count($created),
        ], 201);
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
