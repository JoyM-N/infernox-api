<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('robot_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('robot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('incident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('command_type');
            // JSON lets each command type carry its own data shape
            $table->json('payload')->nullable();

            $table->enum('status', [
                'pending',
                'sent',
                'acknowledged',
                'executed',
                'failed',
                'expired',
            ])->default('pending');
            $table->timestamp('issued_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            // Most critical query — robot polls this constantly
            $table->index(['robot_id', 'status']);

            $table->index('incident_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_commands');
    }
};