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
        Schema::create('audit_day_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->enum('format', ['zip', 'pdf', 'both'])->default('both');
            $table->boolean('include_all_evidences')->default(true);
            $table->boolean('include_full_audit_trail')->default(true);
            $table->string('file_path')->nullable(); // Path al file generato (ZIP o PDF)
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('audit_id');
            $table->index('generated_by');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_day_packs');
    }
};
