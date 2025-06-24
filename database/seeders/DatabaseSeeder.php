<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Option 1: créer un utilisateur spécifique
        /*
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */

        // Option 2: appeler des seeders séparés (ex UserSeeder)
        $this->call([
            UserSeeder::class,
            // autres seeders si besoin
        ]);
    }
}
