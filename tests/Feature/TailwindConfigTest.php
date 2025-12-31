<?php

namespace Tests\Feature;

use Tests\TestCase;

class TailwindConfigTest extends TestCase
{
    /**
     * Test that the Enterprise Audit design system colors are defined in the CSS
     */
    public function test_audit_colors_are_defined_in_css(): void
    {
        $cssPath = public_path('build/assets/app-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'CSS file should be generated');

        $cssContent = file_get_contents($cssFiles[0]);

        // Check for semantic audit colors
        $this->assertStringContainsString('--color-audit-missing', $cssContent, 'audit-missing color should be defined');
        $this->assertStringContainsString('--color-audit-completed', $cssContent, 'audit-completed color should be defined');
        $this->assertStringContainsString('--color-audit-border', $cssContent, 'audit-border color should be defined');

        // Check for base colors
        $this->assertStringContainsString('--color-success-600', $cssContent, 'success-600 color should be defined');
        $this->assertStringContainsString('--color-warning-600', $cssContent, 'warning-600 color should be defined');
        $this->assertStringContainsString('--color-danger-600', $cssContent, 'danger-600 color should be defined');

        // Check for neutral colors
        $this->assertStringContainsString('--color-neutral-', $cssContent, 'neutral colors should be defined');

        // Check for audit-specific spacing and radius
        $this->assertStringContainsString('--radius-audit', $cssContent, 'audit radius should be defined');
    }

    /**
     * Test that the CSS file contains the correct color values
     */
    public function test_audit_colors_have_correct_values(): void
    {
        $cssPath = public_path('build/assets/app-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'CSS file should be generated');

        $cssContent = file_get_contents($cssFiles[0]);

        // Check for specific color values
        $this->assertStringContainsString('#039855', $cssContent, 'success-600 should be #039855');
        $this->assertStringContainsString('#dc6803', $cssContent, 'warning-600 should be #dc6803');
        $this->assertStringContainsString('#d92d20', $cssContent, 'danger-600 should be #d92d20');
    }
}
