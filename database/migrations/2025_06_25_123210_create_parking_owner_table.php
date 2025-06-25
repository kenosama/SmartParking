<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parking_owner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['co_owner'])->default('co_owner');
            $table->timestamps();

            $table->unique(['parking_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_owner');
    }
};