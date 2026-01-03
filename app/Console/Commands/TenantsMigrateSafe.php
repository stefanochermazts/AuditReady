<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TenantsMigrateSafe extends Command
{
    /**
     * Migrate all tenant databases, skipping tenants whose DB is missing.
     *
     * This is useful in local/dev environments where stale tenant records may exist.
     */
    protected $signature = 'tenants:migrate-safe {--tenants=* : Tenant IDs to migrate (default: all)}';

    protected $description = 'Run tenant migrations for all tenants with existing databases (skips missing DBs)';

    public function handle(): int
    {
        /** @var array<int, string> $tenantIds */
        $tenantIds = (array) $this->option('tenants');

        $query = Tenant::query();
        if (! empty($tenantIds)) {
            $query->whereIn('id', $tenantIds);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to migrate.');
            return Command::SUCCESS;
        }

        $params = config('tenancy.migration_parameters', []);

        $this->info("Running tenant migrations (safe) for {$tenants->count()} tenant(s)...");

        $migrated = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            try {
                $dbName = $tenant->database()->getName();
                $dbExists = $tenant->database()->manager()->databaseExists($dbName);

                if (! $dbExists) {
                    $skipped++;
                    $this->warn("Skipping tenant {$tenant->id}: database '{$dbName}' does not exist.");
                    Log::warning('Skipping tenant migrate: tenant database does not exist', [
                        'tenant_id' => $tenant->id,
                        'database' => $dbName,
                    ]);
                    continue;
                }

                $this->line("Tenant: {$tenant->id}");

                $command = $this;
                $tenant->run(function () use ($params, $command): void {
                    Artisan::call('migrate', $params);
                    $output = Artisan::output();
                    if (! empty($output)) {
                        $command->output->write($output);
                    }
                });

                $migrated++;
            } catch (\Throwable $e) {
                $this->error("Failed migrating tenant {$tenant->id}: {$e->getMessage()}");
                Log::error('Tenant migration failed (safe command)', [
                    'tenant_id' => $tenant->id,
                    'exception' => $e,
                ]);
                // Continue migrating other tenants.
            }
        }

        $this->newLine();
        $this->info("Done. Migrated: {$migrated}. Skipped (missing DB): {$skipped}. Total: {$tenants->count()}.");

        return Command::SUCCESS;
    }
}

