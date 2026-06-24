<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('robot_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->float('temperature')->nullable();
            $table->float('smoke_level')->nullable();
            $table->boolean('fire_detected')->default(false);            // Stored in S3/local storage, we just save the path here
            $table->string('image_path')->nullable();

            // The complete raw payload the robot sent because robot firmware changes over time. Storing raw JSON ensures we never lose data even if our schema hasn't caught up yet.
            $table->json('raw_payload')->nullable();

            //this is the robot's clock, not the server clock,telemetry goes through a queue before being saved
            // By the time it hits the DB, server time could be 2-3 seconds late
            // recorded_at tells us EXACTLY when the robot saw this data
            $table->timestamp('recorded_at');

            $table->timestamps();
            // Indexes make queries fast
            $table->index(['robot_id', 'recorded_at']);
            $table->index('fire_detected');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_readings');
    }
};