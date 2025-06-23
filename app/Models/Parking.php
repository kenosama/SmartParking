<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Reservation;
use App\Models\ParkingSpot;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Parking extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'street',
        'location_number', // like house number
        'zip_code',
        'city',
        'country',
        'total_capacity',
        'is_open_24h',
        'opening_hours', //nullable if open 24/24
        'opening_days', //format: 1,2,3....7 nullable if open 24/24
        'user_id',
    ];
/**
 * the owner of the parking (a user)
 */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

/**
 * The reservations made on this parking
 */

public function reservations(): HasManyThrough
{
        return $this->hasManyThrough(
        Reservation::class, 
        ParkingSpot::class
    );
    }

    public function spots(): HasMany
{
    return $this->hasMany(ParkingSpot::class);
}
}