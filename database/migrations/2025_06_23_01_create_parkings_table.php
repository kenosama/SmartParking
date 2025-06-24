<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This method creates the 'parkings' table with relevant fields and constraints.
     */
    public function up(): void
    {
        Schema::create('parkings', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Name of the parking lot
            $table->string('street'); // Street name
            $table->string('location_number'); // Number in the street
            $table->string('zip_code'); // ZIP or postal code
            $table->string('city'); // City where the parking is located
            $table->string('country'); // Country
            $table->integer('total_capacity'); // Total number of parking spots
            $table->boolean('is_open_24h')->default(true); // Whether the parking is open 24/7
            $table->string('opening_hours')->nullable(); // If not open 24/7, defines daily hours (nullable)
            $table->string('opening_days')->nullable(); // Days of the week open (e.g., 1-7), nullable if 24/7
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner of the parking
            $table->boolean('is_active')->default(true); // Whether the parking is active (for soft delete)
            $table->unique(['street', 'location_number', 'zip_code', 'city']); // Unique constraint on location
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     * This method drops the 'parkings' table.
     */
    public function down(): void
    {
        Schema::dropIfExists('parkings');
    }
};
