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
        Schema::create('gap_snapshot_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gap_snapshot_id')->constrained('gap_snapshots')->onDelete('cascade');
            $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
            $table->enum('response', ['yes', 'no', 'partial', 'not_applicable'])->default('no');
            $table->text('notes')->nullable();
            $table->json('evidence_ids')->nullable(); // array di evidence IDs collegati
            $table->timestamps();
            
            // Unique constraint: one response per control per snapshot
            $table->unique(['gap_snapshot_id', 'control_id']);
            
            // Indexes
            $table->index('gap_snapshot_id');
            $table->index('control_id');
            $table->index('response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gap_snapshot_responses');
    }
};
