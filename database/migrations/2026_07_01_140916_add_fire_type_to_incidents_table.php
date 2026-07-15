<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Fire classification based on sensor data
            $table->enum('fire_type', [
                'class_a',     
                                
                'class_b',     

                'class_c',      

                'class_d',      

                'class_f',     

                'unknown',      
            ])->nullable()->after('severity');

            $table->string('recommended_extinguisher')->nullable()->after('fire_type');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['fire_type', 'recommended_extinguisher']);
        });
    }
};