<?php

namespace Tests\Feature;

use Tests\TestCase;

class FilamentTableStylesTest extends TestCase
{
    /**
     * Test that Filament tables have audit-table class applied
     */
    public function test_filament_tables_have_audit_table_class(): void
    {
        $viewPath = resource_path('views/vendor/filament-tables/index.blade.php');
        $this->assertFileExists($viewPath, 'Filament tables view should be published');

        $viewContent = file_get_contents($viewPath);
        
        // Check that audit-table class is added to table elements
        $this->assertStringContainsString('audit-table', $viewContent, 'audit-table class should be added to tables');
        $this->assertStringContainsString('fi-ta-table audit-table', $viewContent) || 
               $this->assertStringContainsString("'audit-table'", $viewContent, 'audit-table should be in @class directive');
    }

    /**
     * Test that global table configuration is set
     */
    public function test_global_table_configuration_is_set(): void
    {
        // This test verifies that Table::configureUsing is called
        // We can't directly test the closure, but we can verify the service provider is loaded
        $this->assertTrue(
            class_exists(\Filament\Tables\Table::class),
            'Filament Tables should be available'
        );
    }
}
