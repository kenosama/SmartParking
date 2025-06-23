<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\ParkingSpot;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parking_spot_id',
        'reserved_date',
        'end_date',
        'start_time',
        'end_time',
        'license_plate',
        'status', // facultatif : ex. "pending", "confirmed", "cancelled"
    ];

    /**
     * L'utilisateur qui a effectué cette réservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La place de parking réservée.
     */
    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    /**
     * (Facultatif) Le parking parent via la place.
     * Pour accéder directement au parking depuis une réservation.
     */
    public function parking(): BelongsTo
    {
        return $this->parkingSpot?->parking();
    }
}