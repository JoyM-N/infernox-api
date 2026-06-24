<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('robot_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'open',//just detected, no action yet
                'investigating',//operator is looking at it
                'suppressing',//robot is actively fighting the fire
                'resolved',//fire is out, incident closed
                'false_alarm',//turned out not to be a real fire
            ])->default('open');     
             // How serious is this incident?
             $table->enum('severity', [
                'low',       // smoke detected, no confirmed fire
                'medium',    // fire detected, contained area
                'high',      // fire spreading
                'critical',  // large fire, immediate danger
            ])->default('medium');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->float('peak_temperature')->nullable();
            $table->float('peak_smoke_level')->nullable();            // This is the robot's clock — same reason as telemetry
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'detected_at']);
            $table->index('robot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};