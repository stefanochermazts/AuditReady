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
        Schema::table('audits', function (Blueprint $table) {
            // Campo opzionale per collegare una matrice ownership a un audit
            // Non è una foreign key perché la matrice ownership è un concetto logico,
            // non una tabella separata (è la vista aggregata di control_owners)
            // Questo campo può essere usato per tracciare quale snapshot di ownership
            // è stato usato per un audit specifico
            $table->timestamp('control_ownership_snapshot_at')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            //
        });
    }
};
