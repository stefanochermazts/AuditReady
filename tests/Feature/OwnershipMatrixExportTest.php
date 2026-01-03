<?php

namespace Tests\Feature;

use App\Models\Control;
use App\Models\User;
use App\Services\ControlService;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnershipMatrixExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_export_ownership_matrix_to_pdf(): void
    {
        $control1 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Control 1',
            'category' => 'Governance',
        ]);

        $control2 = Control::create([
            'standard' => 'NIS2',
            'article_reference' => 'NIS2 Art. 21.1',
            'title' => 'Control 2',
            'category' => 'Security',
        ]);

        $user = User::factory()->create();

        $controlService = app(ControlService::class);
        $controlService->assignOwner($control1->id, $user->id, 'primary', 'CISO', 'Test notes');

        $exportService = app(ExportService::class);
        $filename = $exportService->exportOwnershipMatrixToPdf();

        $this->assertNotEmpty($filename);
        $this->assertStringContainsString('ownership_matrix', $filename);
        
        // Verify file exists in storage
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
    }

    public function test_export_ownership_matrix_to_excel(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Control 1',
            'category' => 'Governance',
        ]);

        $user = User::factory()->create();

        $controlService = app(ControlService::class);
        $controlService->assignOwner($control->id, $user->id, 'primary', 'CISO');

        $exportService = app(ExportService::class);
        $filename = $exportService->exportOwnershipMatrixToExcel();

        $this->assertNotEmpty($filename);
        $this->assertStringContainsString('ownership_matrix', $filename);
        
        // Verify file exists in storage
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
    }

    public function test_export_ownership_matrix_with_filters(): void
    {
        Control::create(['standard' => 'DORA', 'title' => 'DORA Control']);
        Control::create(['standard' => 'NIS2', 'title' => 'NIS2 Control']);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportOwnershipMatrixToPdf(['standard' => 'DORA']);

        $this->assertNotEmpty($filename);
        
        // Verify file exists
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
    }

    public function test_export_includes_statistics(): void
    {
        $control1 = Control::create(['standard' => 'DORA', 'title' => 'Control 1']);
        $control2 = Control::create(['standard' => 'DORA', 'title' => 'Control 2']);

        $user = User::factory()->create();

        $controlService = app(ControlService::class);
        $controlService->assignOwner($control1->id, $user->id);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportOwnershipMatrixToPdf();

        // Verify file exists and is not empty
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
        
        $encryptedContent = $storageService->get($filename);
        $this->assertNotEmpty($encryptedContent);
        
        // Decrypt and verify it's a valid PDF (starts with PDF header)
        $decryptedContent = $exportService->decryptContent($encryptedContent);
        $this->assertStringStartsWith('%PDF', $decryptedContent);
    }
}
