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
        Schema::create('policy_control_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
            $table->text('coverage_notes')->nullable(); // Dettagli su come la policy copre il controllo
            $table->foreignId('mapped_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate mappings
            $table->unique(['policy_id', 'control_id']);
            $table->index('policy_id');
            $table->index('control_id');
            $table->index('mapped_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_control_mappings');
    }
};
