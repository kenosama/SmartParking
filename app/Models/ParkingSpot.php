<?php

namespace App\Models;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ParkingSpot
 *
 * Represents a specific parking spot inside a parking facility.
 */
class ParkingSpot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identifier',             // e.g., "1A", "2B" - Human-readable label for the spot
        'parking_id',             // Foreign key referencing the parking facility
        'user_id',                // Owner of the parking spot
        'allow_electric_charge',  // Boolean flag for electric car compatibility
        'is_available',           // Is the spot currently reservable?
        'per_day_only',           // Only rentable per day (not hourly)
        'price_per_day',          // Daily price for reservation
        'price_per_hour',         // Hourly price for reservation
        'note',                   // Optional note or description
    ];

    /**
     * Get the parking facility this spot belongs to.
     *
     * @return BelongsTo
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class);
    }

    /**
     * Get the owner of this parking spot.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all reservations made for this spot.
     *
     * @return HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
