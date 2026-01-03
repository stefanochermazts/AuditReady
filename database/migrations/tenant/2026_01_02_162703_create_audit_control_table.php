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
        Schema::create('audit_control', function (Blueprint $table) {
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
            $table->timestamps();

            // Composite primary key
            $table->primary(['audit_id', 'control_id']);

            // Indexes for performance
            $table->index('audit_id');
            $table->index('control_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_control');
    }
};
