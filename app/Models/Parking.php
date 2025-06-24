<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\User;
use App\Models\Reservation;
use App\Models\ParkingSpot;

/**
 * Class Parking
 * 
 * Represents a parking structure owned by a user.
 * A parking can have multiple spots and reservations through those spots.
 */
class Parking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'street',
        'location_number', // Like house number
        'zip_code',
        'city',
        'country',
        'total_capacity',
        'is_open_24h',
        'opening_hours',    // Nullable if open 24/7
        'opening_days',     // Format: 1,2,3,...7. Nullable if open 24/7
        'user_id',
    ];

    /**
     * Get the user (owner) that owns the parking.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all reservations made through the parking spots of this parking.
     *
     * @return HasManyThrough
     */
    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reservation::class,
            ParkingSpot::class
        );
    }

    /**
     * Get all parking spots associated with this parking.
     *
     * @return HasMany
     */
    public function spots(): HasMany
    {
        return $this->hasMany(ParkingSpot::class);
    }
}