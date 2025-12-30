<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantTestSeeder extends Seeder
{
    /**
     * Run the database seeds for tenant.
     * This seeder is used to populate tenant databases with test data.
     */
    public function run(): void
    {
        // Create test admin user
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✓ Test admin user created (ID: {$adminId})");
        $this->command->info("  Email: admin@test.local");
        $this->command->info("  Password: password");
        
        // Roles and permissions will be seeded in Step 5 (RBAC)
        // For now, we just create the user
    }
}
