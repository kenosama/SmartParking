<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Reservation;


class Parking extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'capacity',
        'opening_hours',
        'opening_days',
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

 public function reservations(): HasMany
 {
    return $this->hasMany(Reservation::class);
 }
}
