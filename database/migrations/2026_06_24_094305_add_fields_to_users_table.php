<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Is this operator account active?
            // Deactivated accounts cannot log in
            // We use this instead of deleting accounts
            // to preserve audit history
            $table->boolean('is_active')->default(true)->after('password');

            // When did this operator last log in?
            // Useful for security auditing
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};