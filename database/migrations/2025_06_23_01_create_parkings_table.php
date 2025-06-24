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
            $table->string('country');
            $table->integer('total_capacity');
            $table->boolean('is_open_24h')->default(true);
            $table->string('opening_hours')->nullable(); // placer null si ouvert 24/24h
            $table->string('opening_days')->nullable(true); //Format : 1,2,3...to 7, NULL si ouvert 24/24.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->unique(['street', 'location_number', 'zip_code', 'city']);
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
