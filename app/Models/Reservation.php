<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Parking;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parking_id',
        'reserved_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * The user who made the reservation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The reserved parking
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class);
    }
}
