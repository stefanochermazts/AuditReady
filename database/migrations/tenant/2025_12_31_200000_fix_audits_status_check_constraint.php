<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This project uses PostgreSQL in production. Laravel implements `enum()`
        // as a CHECK constraint on Postgres, named like: audits_status_check.
        // Our app uses `closed`, but the original migration allowed `completed`.
        // Fix the constraint to include `closed` (and keep `archived` reserved).

        if (! Schema::hasTable('audits')) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            // SQLite/MySQL handle enums differently; no-op for dev.
            return;
        }

        DB::statement("ALTER TABLE audits DROP CONSTRAINT IF EXISTS audits_status_check");
        DB::statement(
            "ALTER TABLE audits ADD CONSTRAINT audits_status_check CHECK (status IN ('draft', 'in_progress', 'closed', 'archived'))"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('audits')) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Restore previous constraint (best-effort).
        DB::statement("ALTER TABLE audits DROP CONSTRAINT IF EXISTS audits_status_check");
        DB::statement(
            "ALTER TABLE audits ADD CONSTRAINT audits_status_check CHECK (status IN ('draft', 'in_progress', 'completed', 'archived'))"
        );
    }
};

