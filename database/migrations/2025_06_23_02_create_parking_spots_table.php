<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations to create the parking_spots table.
     */
    public function up(): void
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key linking to the parking this spot belongs to
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');

            // Owner of the spot
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Spot identifier (e.g., A1, B2, etc.)
            $table->string('identifier');

            // Charging station availability
            $table->boolean('allow_electric_charge')->default(false);

            // Availability and booking status
            $table->boolean('is_available')->default(true);
            $table->boolean('is_booked')->default(false);

            // If the spot is available only for daily reservations
            $table->boolean('per_day_only');

            // Pricing
            $table->decimal('price_per_day', 10, 2)->default(99);
            $table->decimal('price_per_hour', 6, 2)->default(3.5);

            // Notes or additional information
            $table->text('note')->nullable();

            // Timestamps for created_at and updated_at
            $table->timestamps();

            // Ensure no duplicate identifier within the same parking
            $table->unique(['parking_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations by dropping the parking_spots table.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
