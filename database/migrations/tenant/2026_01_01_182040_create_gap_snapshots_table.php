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
        Schema::create('gap_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('set null');
            $table->string('name'); // es: "DORA Gap Snapshot Q1 2025"
            $table->enum('standard', ['DORA', 'NIS2', 'both'])->default('both');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('audit_id');
            $table->index('standard');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gap_snapshots');
    }
};
