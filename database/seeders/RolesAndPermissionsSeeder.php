<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Tenant/Organization management
            'manage-tenant',
            'manage-users',
            'assign-roles',
            'delete-organization',

            // Audit management
            'create-audit',
            'view-audit',
            'update-audit',
            'close-audit',
            'delete-audit',
            'export-audit',

            // Evidence management
            'upload-evidence',
            'view-evidence',
            'update-evidence',
            'delete-evidence',
            'view-all-evidence', // For Audit Managers to see all evidence
            'view-own-evidence', // For Contributors to see only their evidence

            // Export
            'export-pdf',
            'export-csv',

            // API (for External Uploader)
            'api-upload',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        // 1. Organization Owner
        $ownerRole = Role::firstOrCreate(['name' => 'Organization Owner', 'guard_name' => 'web']);
        $ownerRole->syncPermissions([
            'manage-tenant',
            'manage-users',
            'assign-roles',
            'delete-organization',
            'create-audit',
            'view-audit',
            'update-audit',
            'close-audit',
            'delete-audit',
            'export-audit',
            'upload-evidence',
            'view-evidence',
            'update-evidence',
            'delete-evidence',
            'view-all-evidence',
            'export-pdf',
            'export-csv',
        ]);

        // 2. Audit Manager
        $auditManagerRole = Role::firstOrCreate(['name' => 'Audit Manager', 'guard_name' => 'web']);
        $auditManagerRole->syncPermissions([
            'create-audit',
            'view-audit',
            'update-audit',
            'close-audit',
            'export-audit',
            'view-all-evidence',
            'export-pdf',
            'export-csv',
        ]);

        // 3. Contributor
        $contributorRole = Role::firstOrCreate(['name' => 'Contributor', 'guard_name' => 'web']);
        $contributorRole->syncPermissions([
            'view-audit',
            'upload-evidence',
            'view-evidence',
            'update-evidence',
            'delete-evidence',
            'view-own-evidence',
        ]);

        // 4. Viewer
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions([
            'view-audit',
            'view-evidence',
            'view-all-evidence',
        ]);

        // 5. External Uploader
        $externalUploaderRole = Role::firstOrCreate(['name' => 'External Uploader', 'guard_name' => 'web']);
        $externalUploaderRole->syncPermissions([
            'api-upload',
        ]);

        $this->command->info('✓ Roles and permissions created successfully');
        $this->command->info('  - Organization Owner');
        $this->command->info('  - Audit Manager');
        $this->command->info('  - Contributor');
        $this->command->info('  - Viewer');
        $this->command->info('  - External Uploader');
    }
}
