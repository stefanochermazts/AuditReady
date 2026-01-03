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
        Schema::create('controls', function (Blueprint $table) {
            $table->id();
            $table->enum('standard', ['DORA', 'NIS2', 'ISO27001', 'custom'])->default('custom');
            $table->string('article_reference')->nullable(); // es: "DORA Art. 8.1"
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // es: "Risk Management", "Incident Response"
            $table->string('tenant_id')->nullable(); // per custom controls (opzionale, isolamento già garantito da multi-DB)
            $table->timestamps();
            
            // Indexes
            $table->index('standard');
            $table->index('category');
            $table->index(['standard', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controls');
    }
};
