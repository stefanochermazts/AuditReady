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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->default('1.0');
            $table->date('approval_date')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('evidence_id')->nullable()->constrained('evidences')->onDelete('set null'); // File policy (opzionale)
            $table->string('internal_link')->nullable(); // Link intranet (opzionale)
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('owner_id');
            $table->index('evidence_id');
            $table->index(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
