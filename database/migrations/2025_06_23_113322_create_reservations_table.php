<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the 'reservations' table with appropriate constraints
     * and fields for tracking parking spot reservations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            // Reference to the user who made the reservation
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Reference to the parking area
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');

            // Reference to the specific parking spot
            $table->foreignId('parking_spot_id')->constrained()->onDelete('cascade');

            // Reservation start and end datetimes
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            // Optional license plate linked to the reservation
            $table->string('license_plate')->nullable();
            // Status of the reservation
            // - active: ongoing reservation
            // - cancelled_by_user: cancelled by the person who booked it
            // - cancelled_by_owner: cancelled by the parking owner
            // - cancelled_by_admin: cancelled by admin
            // - manual_override: created manually by admin without user context
            // - done: completed reservation
            $table->enum('status', [
                'active',
                'cancelled_by_user',
                'cancelled_by_owner',
                'cancelled_by_admin',
                'manual_override',
                'done'
            ])->default('active');

            $table->uuid('group_token')->nullable()->index();
            // Unique constraint to prevent overlapping reservations on the same spot at the same datetime
            $table->unique(['parking_spot_id', 'start_datetime', 'end_datetime'], 'unique_reservation_timeframe');

            // Laravel timestamps: created_at and updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the 'reservations' table.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
