<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('identifier'); // ex : A1, B2, C3
            $table->boolean('allow_electric_charge')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('per_day_only');
            $table->decimal('price_per_day',10,2)->default(99)->nullable();
            $table->decimal('price_per_hour',6,2)->default(3.5)->nullable();
            $table->timestamps();
            $table->text('note')->nullable();
            $table->unique(['parking_id', 'identifier']); // Évite les doublons dans un même parking
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
