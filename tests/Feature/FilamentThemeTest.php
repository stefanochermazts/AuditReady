<?php

namespace Tests\Feature;

use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Tests\TestCase;

class FilamentThemeTest extends TestCase
{
    /**
     * Test that the Filament theme is registered correctly
     */
    public function test_filament_theme_is_registered(): void
    {
        $provider = new AdminPanelProvider($this->app);
        $panel = $provider->panel(new Panel('admin'));

        // Check that viteTheme is configured
        $reflection = new \ReflectionClass($panel);
        $viteThemeProperty = $reflection->getProperty('viteTheme');
        $viteThemeProperty->setAccessible(true);
        $viteTheme = $viteThemeProperty->getValue($panel);

        $this->assertNotNull($viteTheme, 'Vite theme should be registered');
        $this->assertEquals('resources/css/filament/admin/theme.css', $viteTheme, 'Theme path should match');
    }

    /**
     * Test that the theme CSS file exists and is compiled
     */
    public function test_theme_css_file_exists(): void
    {
        $themePath = resource_path('css/filament/admin/theme.css');
        $this->assertFileExists($themePath, 'Theme CSS file should exist');

        $cssContent = file_get_contents($themePath);
        $this->assertStringContainsString('@import', $cssContent, 'Theme should import Tailwind');
        $this->assertStringContainsString('@theme', $cssContent, 'Theme should define @theme block');
        $this->assertStringContainsString('audit.css', $cssContent, 'Theme should import audit.css');
    }

    /**
     * Test that the compiled theme CSS contains audit classes
     */
    public function test_compiled_theme_contains_audit_classes(): void
    {
        $cssPath = public_path('build/assets/theme-*.css');
        $cssFiles = glob($cssPath);

        $this->assertNotEmpty($cssFiles, 'Compiled theme CSS should exist');

        $cssContent = file_get_contents($cssFiles[0]);
        $this->assertStringContainsString('.audit-table', $cssContent, 'Compiled theme should contain audit-table');
        $this->assertStringContainsString('.audit-badge', $cssContent, 'Compiled theme should contain audit-badge');
    }
}
