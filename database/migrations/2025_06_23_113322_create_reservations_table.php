<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');
            $table->foreignId('parking_spot_id')->constrained()->onDelete('cascade'); // ⚠️ Correctement défini maintenant

            $table->date('reserved_date'); // début de réservation
            $table->date('end_date')->nullable(); // fin (optionnelle)
            $table->time('start_time');
            $table->time('end_time');
            $table->string('license_plate')->nullable();

            $table->enum('status', [
                'active',
                'cancelled_by_user',
                'cancelled_by_owner',
                'done'
            ])->default('active');


            $table->timestamps();

            // Optionnel : empêcher les réservations doublons
            $table->unique(['parking_spot_id', 'reserved_date', 'start_time', 'end_time'], 'unique_reservation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
