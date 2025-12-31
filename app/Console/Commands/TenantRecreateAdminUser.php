<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class TenantRecreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:recreate-admin 
                            {tenant : The tenant ID or domain}
                            {--email=admin@test.com : Email for the admin user}
                            {--password=password : Password for the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreate admin user for an existing tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantIdentifier = $this->argument('tenant');
        $email = $this->option('email');
        $password = $this->option('password');

        // Find tenant by ID or domain
        $tenant = Tenant::find($tenantIdentifier);
        if (!$tenant) {
            // Try to find by domain
            $tenant = Tenant::whereHas('domains', function ($query) use ($tenantIdentifier) {
                $query->where('domain', $tenantIdentifier);
            })->first();
        }

        if (!$tenant) {
            $this->error("Tenant not found: {$tenantIdentifier}");
            return Command::FAILURE;
        }

        $this->info("Recreating admin user for tenant: {$tenant->id}");

        try {
            $userId = null;
            $tenant->run(function () use ($email, $password, &$userId) {
                // Seed roles and permissions first
                Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
                
                // Check if user already exists
                $existingUser = \App\Models\User::where('email', $email)->first();
                if ($existingUser) {
                    $this->warn("User with email {$email} already exists. Updating...");
                    $existingUser->update([
                        'password' => Hash::make($password),
                        'email_verified_at' => now(),
                    ]);
                    $existingUser->syncRoles(['Organization Owner']);
                    $userId = $existingUser->id;
                } else {
                    // Create user in tenant database
                    $user = \App\Models\User::create([
                        'name' => 'Organization Owner',
                        'email' => $email,
                        'password' => Hash::make($password),
                        'email_verified_at' => now(),
                    ]);

                    // Assign Organization Owner role
                    $user->assignRole('Organization Owner');
                    $userId = $user->id;
                }
            });
            
            $this->info("✓ Admin user created/updated with ID: {$userId}");
            $this->info("✓ Organization Owner role assigned");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Tenant ID', $tenant->id],
                    ['Email', $email],
                    ['Password', $password],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to recreate admin user: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
