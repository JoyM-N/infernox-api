<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Operator accountable for handling this incident
            $table->foreignId('assigned_operator_id')
                ->nullable()
                ->after('robot_id')
                ->constrained('users')
                ->nullOnDelete();

            // Super admin can lock after mitigation — no further edits
            $table->boolean('is_locked')->default(false)->after('resolved_at');
            $table->foreignId('locked_by')
                ->nullable()
                ->after('is_locked')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_operator_id');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn(['is_locked', 'locked_at']);
        });
    }
};
