<?php

namespace Tests\Feature;

use Tests\TestCase;

class FilamentFormStylesTest extends TestCase
{
    /**
     * Test that Filament forms have audit-form classes applied
     */
    public function test_filament_forms_have_audit_form_classes(): void
    {
        $fieldWrapperPath = resource_path('views/vendor/filament-forms/components/field-wrapper.blade.php');
        $this->assertFileExists($fieldWrapperPath, 'Filament forms field-wrapper view should be published');

        $fieldWrapperContent = file_get_contents($fieldWrapperPath);
        
        // Check that audit-form-label class is added
        $this->assertStringContainsString('audit-form-label', $fieldWrapperContent, 'audit-form-label class should be added to labels');
        $this->assertStringContainsString('audit-form-error', $fieldWrapperContent, 'audit-form-error class should be added to error messages');
    }

    /**
     * Test that text inputs have audit-form-input class
     */
    public function test_text_inputs_have_audit_form_input_class(): void
    {
        $textInputPath = resource_path('views/vendor/filament-forms/components/text-input.blade.php');
        $this->assertFileExists($textInputPath, 'Filament forms text-input view should be published');

        $textInputContent = file_get_contents($textInputPath);
        
        // Check that audit-form-input class is added
        $this->assertStringContainsString('audit-form-input', $textInputContent, 'audit-form-input class should be added to text inputs');
    }

    /**
     * Test that textarea has audit-form-input class
     */
    public function test_textarea_has_audit_form_input_class(): void
    {
        $textareaPath = resource_path('views/vendor/filament-forms/components/textarea.blade.php');
        $this->assertFileExists($textareaPath, 'Filament forms textarea view should be published');

        $textareaContent = file_get_contents($textareaPath);
        
        // Check that audit-form-input class is added
        $this->assertStringContainsString('audit-form-input', $textareaContent, 'audit-form-input class should be added to textarea');
    }

    /**
     * Test that select has audit-form-input class
     */
    public function test_select_has_audit_form_input_class(): void
    {
        $selectPath = resource_path('views/vendor/filament-forms/components/select.blade.php');
        $this->assertFileExists($selectPath, 'Filament forms select view should be published');

        $selectContent = file_get_contents($selectPath);
        
        // Check that audit-form-input class is added
        $this->assertStringContainsString('audit-form-input', $selectContent, 'audit-form-input class should be added to select');
    }

    /**
     * Test that page headers have audit-page classes
     */
    public function test_page_headers_have_audit_page_classes(): void
    {
        $headerPath = resource_path('views/vendor/filament-panels/components/header/index.blade.php');
        $this->assertFileExists($headerPath, 'Filament panels header view should be published');

        $headerContent = file_get_contents($headerPath);
        
        // Check that audit-page-header and audit-page-title classes are added
        $this->assertStringContainsString('audit-page-header', $headerContent, 'audit-page-header class should be added to headers');
        $this->assertStringContainsString('audit-page-title', $headerContent, 'audit-page-title class should be added to headings');
    }
}
