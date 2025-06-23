<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Parking;
use App\Models\ParkingSpot;
use App\Models\Reservation;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_admin',
        'is_owner',
        'is_tenant',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


/**
 * A user can have more than one parking
 */
public function parkings(): HasMany
{
    return $this->hasMany(Parking::class);
}

/**
 * A user can make more than one reservation
 */

 public function reservations(): HasMany
 {
    return $this->hasMany(Reservation::class);
 }

/**
 * A user can posses multiple parking spots
 */
    public function parkingSpots(): HasMany
    {
        return $this->hasMany(ParkingSpot::class);
    }
/**
* Helper methods to check user roles.
*/
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isOwner(): bool
    {
        return $this->is_owner;
    }

    public function isTenant(): bool
    {
        return $this->is_tenant;
    }
}