<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;

class ParkingSpot extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',             // Ex: "1A", "2B"
        'parking_id',             // Clé étrangère vers parkings
        'user_id',                // Clé étrangère vers users (le proprio de la place)
        'allow_electric_charge',  // booléen
        'is_available',           // booléen
        'per_day_only',           // booléen
        'price_per_day',          // Prix journalier
        'price_per_hour',         // Prix horaire
        'note',                   // Texte libre
    ];

    /**
     * Le parking auquel cette place appartient.
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class);
    }

    /**
     * Le propriétaire de cette place.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Les réservations effectuées sur cette place.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
