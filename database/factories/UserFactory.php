<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake('fr_BE')->firstName(),
            'last_name' => fake('fr_BE')->lastName(),
            'email' => fake('fr_BE')->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'), // default password
            'remember_token' => Str::random(10),
            'is_admin' => fake()->boolean(10), // 10% chance of admin
            'is_owner' => fake()->boolean(50), // 50% chance of owner
            'is_tenant' => fake()->boolean(50), // 50% chance of tenant
            'is_active' => true, // active by default
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
