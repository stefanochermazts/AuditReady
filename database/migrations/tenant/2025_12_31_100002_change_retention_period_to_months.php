<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('retention_period_years');
            
            // Add new column in months (default 84 months = 7 years)
            $table->integer('retention_period_months')->nullable()->default(84)->after('confidentiality_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            $table->dropColumn('retention_period_months');
            
            $table->integer('retention_period_years')->nullable()->default(7)->after('confidentiality_level');
        });
    }
};
