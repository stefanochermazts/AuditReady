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
        Schema::create('evidence_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_request_id')->constrained('evidence_requests')->onDelete('cascade');
            $table->enum('action', ['created', 'accessed', 'file_uploaded', 'expired', 'cancelled'])->default('accessed');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // File info, etc.
            $table->timestamp('created_at');

            $table->index('evidence_request_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_request_logs');
    }
};
