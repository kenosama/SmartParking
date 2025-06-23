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
            $table->date('reserved_date'); //beging date of the booking.
            $table->date('end_date')->nullable(); //end date of the booking.
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['active','cancelled_by_user','cancelled_by_owner','done'])->default('active');
            // $table->unique(['parking_id', 'reserved_date', 'start_time', 'end_time']);
            $table->timestamps();
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
