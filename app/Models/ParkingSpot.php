<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Parking;
use App\Models\Reservation;

class ParkingSpot extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',      // Ex: "1A", "2B", etc.
        'user_id',         // clé étrangere vers le user
        'parking_id',      // Clé étrangère vers parkings
        'price_per_hours', // prix a l'heure pour le spot en question
        'is_available',    // booléen pour indiquer la dispo
    ];

    /**
     * Le parking auquel cette place appartient.
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class);
    }

    /**
     * Les réservations effectuées sur cette place.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
