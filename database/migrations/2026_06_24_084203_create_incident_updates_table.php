<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->enum('action_taken', [
                'acknowledged',   // operator saw the alert
                'dispatched',     // sent a robot to the scene
                'suppressed',     // fire was suppressed
                'investigated',   // went to check it out
                'escalated',      // called for more help
                'resolved',       // marked incident as done
                'false_alarm',    // confirmed it was nothing
            ])->nullable();

            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
    }
};