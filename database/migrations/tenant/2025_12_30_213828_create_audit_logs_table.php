<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id'); // Tenant UUID (for reference, actual isolation via separate DB)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // created, updated, deleted, restored, viewed, etc.
            $table->string('model_type')->nullable(); // Fully qualified model class name
            $table->unsignedBigInteger('model_id')->nullable(); // ID of the model instance
            $table->json('payload')->nullable(); // Additional data (changes, metadata, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('signature', 64); // HMAC-SHA256 signature for tamper detection
            $table->timestamp('created_at'); // Immutable timestamp (no updated_at)
            
            // Indexes for querying
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
            
            // Note: No updated_at or deleted_at - this is an append-only table
            // No UPDATE or DELETE operations should be performed on this table
        });
        
        // For PostgreSQL, we can add a check constraint to prevent updates
        // For MySQL, we rely on application-level enforcement
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_immutable CHECK (true)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
