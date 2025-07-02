<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer la contrainte FK temporairement
        Schema::table('reservations', function ($table) {
            $table->dropForeign('reservations_parking_spot_id_foreign');
        });

        // Supprimer l’index unique actuel
        DB::statement('DROP INDEX unique_reservation_timeframe ON reservations');

        // Recréer l’index partiel (filtré) – MariaDB >= 10.2+ ne supporte pas les indexes filtrés, donc ce sera à simuler autrement
        DB::statement("
            CREATE UNIQUE INDEX unique_reservation_timeframe 
            ON reservations(parking_spot_id, start_datetime, end_datetime, status)
        ");

        // Remettre la contrainte FK
        Schema::table('reservations', function ($table) {
            $table->foreign('parking_spot_id')
                ->references('id')
                ->on('parking_spots')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function ($table) {
            $table->dropForeign(['parking_spot_id']);
        });

        DB::statement('DROP INDEX unique_reservation_timeframe ON reservations');

        Schema::table('reservations', function ($table) {
            $table->unique(['parking_spot_id', 'start_datetime', 'end_datetime'], 'unique_reservation_timeframe');

            $table->foreign('parking_spot_id')
                ->references('id')
                ->on('parking_spots')
                ->onDelete('cascade');
        });
    }
};