<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\ParkingSpot;

/**
 * Class Reservation
 *
 * Represents a reservation made by a user for a parking spot.
 */
class Reservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'parking_spot_id',
        'parking_id',
        'start_datetime',
        'end_datetime',
        'license_plate',
        'status', // Optional: e.g., "pending", "confirmed", "cancelled"
        'group_token'
    ];

    /**
     * Get the user who made the reservation.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parking spot that was reserved.
     *
     * @return BelongsTo
     */
    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    /**
     * (Optional) Get the parent parking through the reserved spot.
     * Useful for direct access to the parking from the reservation.
     *
     * @return BelongsTo|null
     */
    public function parking(): ?BelongsTo
    {
        return $this->parkingSpot?->parking();
    }
}