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
        Schema::create('parkings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('street');
            $table->string('location_number');
            $table->string('zip_code');
            $table->string('city');
            $table->string('parking_spot_id'); //comment faire pour donner les id de plusieurs place si le user en a plus qu'un.  
            $table->integer('capacity');
            $table->string('opening_hours');
            $table->string('opening_days'); //Format : 1,2,3...to 7.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parkings');
    }
};
