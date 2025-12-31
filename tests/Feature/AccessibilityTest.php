<?php

namespace Tests\Feature;

use App\Filament\Support\StatusBadgeHelper;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    /**
     * Test that status badges always include text labels (not color-only communication)
     */
    public function test_status_badges_have_text_labels(): void
    {
        // Test audit status labels
        $this->assertEquals('Draft', StatusBadgeHelper::getAuditStatusLabel('draft'));
        $this->assertEquals('In Progress', StatusBadgeHelper::getAuditStatusLabel('in_progress'));
        $this->assertEquals('Closed', StatusBadgeHelper::getAuditStatusLabel('closed'));
        $this->assertNotEmpty(StatusBadgeHelper::getAuditStatusLabel('unknown'), 'Unknown status should return a label');

        // Test validation status labels
        $this->assertEquals('Pending', StatusBadgeHelper::getValidationStatusLabel('pending'));
        $this->assertEquals('Approved', StatusBadgeHelper::getValidationStatusLabel('approved'));
        $this->assertEquals('Rejected', StatusBadgeHelper::getValidationStatusLabel('rejected'));
        $this->assertEquals('Needs Revision', StatusBadgeHelper::getValidationStatusLabel('needs_revision'));

        // Test audit type labels
        $this->assertEquals('Internal', StatusBadgeHelper::getAuditTypeLabel('internal'));
        $this->assertEquals('External', StatusBadgeHelper::getAuditTypeLabel('external'));
        $this->assertEquals('Certification', StatusBadgeHelper::getAuditTypeLabel('certification'));
        $this->assertEquals('Compliance', StatusBadgeHelper::getAuditTypeLabel('compliance'));
    }

    /**
     * Test that status badges use semantic colors
     */
    public function test_status_badges_use_semantic_colors(): void
    {
        // Test audit status colors (semantic mapping)
        $this->assertEquals('warning', StatusBadgeHelper::getAuditStatusColor('in_progress'), 'In Progress should use warning (In Review)');
        $this->assertEquals('success', StatusBadgeHelper::getAuditStatusColor('closed'), 'Closed should use success (Completed)');
        $this->assertEquals('danger', StatusBadgeHelper::getValidationStatusColor('rejected'), 'Rejected should use danger (Missing/Risk)');
        $this->assertEquals('warning', StatusBadgeHelper::getValidationStatusColor('needs_revision'), 'Needs Revision should use warning (In Review)');
        $this->assertEquals('success', StatusBadgeHelper::getValidationStatusColor('approved'), 'Approved should use success (Completed)');
    }

    /**
     * Test that focus states are defined in CSS
     */
    public function test_focus_states_are_defined(): void
    {
        $cssPath = public_path('build/assets/theme-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'Theme CSS should be compiled');

        $cssContent = file_get_contents($cssFiles[0]);

        // Check for focus-visible styles
        $this->assertStringContainsString(':focus-visible', $cssContent, 'Focus-visible states should be defined');
        $this->assertStringContainsString('ring-2', $cssContent, 'Focus rings should be 2px');
        $this->assertStringContainsString('ring-offset-2', $cssContent, 'Focus ring offset should be 2px');
    }

    /**
     * Test that form inputs have accessible labels
     */
    public function test_form_inputs_have_accessible_labels(): void
    {
        $fieldWrapperPath = resource_path('views/vendor/filament-forms/components/field-wrapper.blade.php');
        $this->assertFileExists($fieldWrapperPath, 'Field wrapper view should exist');

        $fieldWrapperContent = file_get_contents($fieldWrapperPath);

        // Check that labels are always visible (not sr-only by default)
        $this->assertStringContainsString('fi-fo-field-label', $fieldWrapperContent, 'Field labels should be present');
        $this->assertStringContainsString('audit-form-label', $fieldWrapperContent, 'Audit form label class should be applied');
    }

    /**
     * Test that error messages are accessible
     */
    public function test_error_messages_are_accessible(): void
    {
        $fieldWrapperPath = resource_path('views/vendor/filament-forms/components/field-wrapper.blade.php');
        $fieldWrapperContent = file_get_contents($fieldWrapperPath);

        // Check that error messages have accessible styling
        $this->assertStringContainsString('audit-form-error', $fieldWrapperContent, 'Error messages should have audit-form-error class');
        $this->assertStringContainsString('data-validation-error', $fieldWrapperContent, 'Error messages should have validation error attribute');
    }
}
