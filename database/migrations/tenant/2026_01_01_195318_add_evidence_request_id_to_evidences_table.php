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
            $table->foreignId('evidence_request_id')
                ->nullable()
                ->after('audit_id')
                ->constrained('evidence_requests')
                ->nullOnDelete();

            $table->index('evidence_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            $table->dropForeign(['evidence_request_id']);
            $table->dropIndex(['evidence_request_id']);
            $table->dropColumn('evidence_request_id');
        });
    }
};
