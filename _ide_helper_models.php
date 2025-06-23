<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $street
 * @property string $location_number
 * @property string $zip_code
 * @property string $city
 * @property string $country
 * @property int $total_capacity
 * @property int $is_open_24h
 * @property string|null $opening_hours
 * @property string|null $opening_days
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read int|null $reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ParkingSpot> $spots
 * @property-read int|null $spots_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereIsOpen24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereLocationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereOpeningDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereOpeningHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereTotalCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parking whereZipCode($value)
 */
	class Parking extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $parking_id
 * @property int $user_id
 * @property string $identifier
 * @property int $allow_electric_charge
 * @property int $is_available
 * @property int $per_day_only
 * @property string|null $price_per_day
 * @property string|null $price_per_hour
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $note
 * @property-read \App\Models\Parking $parking
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read int|null $reservations_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereAllowElectricCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereParkingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot wherePerDayOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot wherePricePerDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot wherePricePerHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParkingSpot whereUserId($value)
 */
	class ParkingSpot extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $parking_id
 * @property int $parking_spot_id
 * @property string $reserved_date
 * @property string|null $end_date
 * @property string $start_time
 * @property string $end_time
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ParkingSpot $parkingSpot
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereParkingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereParkingSpotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereReservedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereUserId($value)
 */
	class Reservation extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property int $is_admin
 * @property int $is_owner
 * @property int $is_tenant
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ParkingSpot> $parkingSpots
 * @property-read int|null $parking_spots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Parking> $parkings
 * @property-read int|null $parkings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read int|null $reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsTenant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

