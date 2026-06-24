<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('robots', function (Blueprint $table) {
            // Primary key — we use UUID instead of integer
            // Why: UUIDs are globally unique, harder to guess,
            $table->uuid('id')->primary();
            $table->string('serial_number')->unique();
            $table->string('name');
            $table->string('model');
            // Current status of the robot
            $table->enum('status', [
                'provisioned',    // just registered, never connected
                'online',         // connected and sending telemetry
                'offline',        // was online, now not responding
                'idle',           // online but not doing anything
                'active',         // currently suppressing a fire
                'error',          // something is wrong
                'decommissioned', // retired, no longer in service
            ])->default('provisioned');

            // Last known GPS location
            // nullable because robot hasn't sent location yet on first setup
            $table->decimal('last_known_lat', 10, 7)->nullable();
            $table->decimal('last_known_lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();            // If this is more than 60s ago, robot is considered offline
            $table->timestamp('last_seen_at')->nullable();            $table->timestamp('commissioned_at')->nullable();
            // Soft deletes — when you "delete" a robot it's just hidden
            // The data stays in the database for audit purposes
            $table->softDeletes();
            $table->timestamps();
        });
    }

        public function down(): void
    {
        Schema::dropIfExists('robots');
    }
};