<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Parking;
use App\Models\ParkingSpot;
use App\Models\Reservation;
use Laravel\Sanctum\HasApiTokens;

/**
 * The User model represents authenticated users of the system.
 * Users can have different roles: admin, owner, or tenant.
 * They can also own parkings, parking spots, and make reservations.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Attributes that are mass assignable.
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
     * Attributes that should be hidden during serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting definitions.
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
     * Get the parkings owned by the user.
     *
     * @return HasMany<Parking>
     */
    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }

    /**
     * Get the reservations made by the user.
     *
     * @return HasMany<Reservation>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get the parking spots owned by the user.
     *
     * @return HasMany<ParkingSpot>
     */
    public function parkingSpots(): HasMany
    {
        return $this->hasMany(ParkingSpot::class);
    }

    /**
     * Check if the user has the admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if the user has the owner role.
     *
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->is_owner;
    }

    /**
     * Check if the user has the tenant role.
     *
     * @return bool
     */
    public function isTenant(): bool
    {
        return $this->is_tenant;
    }
}