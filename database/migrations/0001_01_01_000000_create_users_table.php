<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Anonymous migration class for creating necessary authentication-related tables
return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the users, password_reset_tokens, and sessions tables.
     */
    public function up(): void
    {
        // --- Create 'users' table to store user accounts and roles ---
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('first_name'); // User's first name
            $table->string('last_name'); // User's last name
            $table->string('email')->unique(); // Unique email address
            $table->timestamp('email_verified_at')->nullable(); // Timestamp for email verification
            $table->string('password'); // Hashed password

            // Role flags
            $table->boolean('is_admin')->default(false); // Admin flag
            $table->boolean('is_owner')->default(false); // Owner flag
            $table->boolean('is_tenant')->default(true); // Tenant flag (default true)

            $table->boolean('is_active')->default(true); // Indicates if the user account is active
            $table->rememberToken(); // Token for "remember me" authentication
            $table->timestamps(); // created_at and updated_at timestamps
        });

        // --- Create 'password_reset_tokens' table to manage password resets ---
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // Email as primary key
            $table->string('token'); // Reset token
            $table->timestamp('created_at')->nullable(); // Token creation timestamp
        });

        // --- Create 'sessions' table to store user sessions (used for tracking login sessions) ---
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Session ID
            $table->foreignId('user_id')->nullable()->index(); // Optional foreign key to user
            $table->string('ip_address', 45)->nullable(); // IP address (supports IPv6)
            $table->text('user_agent')->nullable(); // Browser user agent
            $table->longText('payload'); // Session data payload
            $table->integer('last_activity')->index(); // Timestamp of last activity
        });
    }

    /**
     * Reverse the migrations.
     * Drops all previously created tables.
     */
    public function down(): void
    {
        // Drop tables in reverse order of creation for safety
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
