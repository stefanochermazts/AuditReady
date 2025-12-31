<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuditCssTest extends TestCase
{
    /**
     * Test that the Enterprise Audit CSS utility classes are defined
     */
    public function test_audit_css_classes_are_defined(): void
    {
        $cssPath = public_path('build/assets/app-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'CSS file should be generated');

        $cssContent = file_get_contents($cssFiles[0]);

        // Check for main utility classes
        $this->assertStringContainsString('.audit-table', $cssContent, 'audit-table class should be defined');
        $this->assertStringContainsString('.audit-badge', $cssContent, 'audit-badge class should be defined');
        $this->assertStringContainsString('.audit-form-label', $cssContent, 'audit-form-label class should be defined');
        $this->assertStringContainsString('.audit-form-input', $cssContent, 'audit-form-input class should be defined');

        // Check for badge variants
        $this->assertStringContainsString('.audit-badge-missing', $cssContent, 'audit-badge-missing should be defined');
        $this->assertStringContainsString('.audit-badge-in-review', $cssContent, 'audit-badge-in-review should be defined');
        $this->assertStringContainsString('.audit-badge-completed', $cssContent, 'audit-badge-completed should be defined');

        // Check for action button classes
        $this->assertStringContainsString('.audit-action-primary', $cssContent, 'audit-action-primary should be defined');
        $this->assertStringContainsString('.audit-action-danger', $cssContent, 'audit-action-danger should be defined');
        $this->assertStringContainsString('.audit-action-export', $cssContent, 'audit-action-export should be defined');

        // Check for layout utilities
        $this->assertStringContainsString('.audit-page-header', $cssContent, 'audit-page-header should be defined');
        $this->assertStringContainsString('.audit-card', $cssContent, 'audit-card should be defined');
    }

    /**
     * Test that CSS classes use the correct color variables
     */
    public function test_audit_css_uses_color_variables(): void
    {
        $cssPath = public_path('build/assets/app-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'CSS file should be generated');

        $cssContent = file_get_contents($cssFiles[0]);

        // Check that CSS uses CSS variables for colors
        $this->assertStringContainsString('var(--color-audit-border)', $cssContent, 'Should use audit-border variable');
        $this->assertStringContainsString('var(--color-audit-text)', $cssContent, 'Should use audit-text variable');
        $this->assertStringContainsString('var(--color-audit-neutral-bg)', $cssContent, 'Should use audit-neutral-bg variable');
    }

    /**
     * Test that focus states are defined for accessibility
     */
    public function test_focus_states_are_defined(): void
    {
        $cssPath = public_path('build/assets/app-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'CSS file should be generated');

        $cssContent = file_get_contents($cssFiles[0]);

        // Check for focus-visible styles
        $this->assertStringContainsString(':focus-visible', $cssContent, 'Focus-visible states should be defined');
        $this->assertStringContainsString('ring-2', $cssContent, 'Focus rings should be defined');
    }
}
