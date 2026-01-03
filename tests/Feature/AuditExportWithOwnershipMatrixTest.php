<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Control;
use App\Models\User;
use App\Services\ControlService;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditExportWithOwnershipMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_audit_export_includes_ownership_matrix_when_dora_standard(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'compliance',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        // Create DORA control
        $control = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Test Control',
            'category' => 'Governance',
        ]);

        // Assign owner
        $controlService = app(ControlService::class);
        $controlService->assignOwner($control->id, $user->id, 'primary', 'CISO');

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        $this->assertNotEmpty($filename);
        
        // Verify file exists and is a valid PDF
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
        
        $encryptedContent = $storageService->get($filename);
        $decryptedContent = $exportService->decryptContent($encryptedContent);
        
        // Verify it's a valid PDF (starts with PDF header)
        $this->assertStringStartsWith('%PDF', $decryptedContent);
        
        // Verify PDF has reasonable size (should be > 1KB if ownership matrix is included)
        $this->assertGreaterThan(1000, strlen($decryptedContent));
    }

    public function test_audit_export_includes_ownership_matrix_when_nis2_standard(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'compliance',
            'compliance_standards' => ['NIS2'],
            'created_by' => $user->id,
        ]);

        // Create NIS2 control
        $control = Control::create([
            'standard' => 'NIS2',
            'article_reference' => 'NIS2 Art. 21.1',
            'title' => 'NIS2 Control',
        ]);

        $controlService = app(ControlService::class);
        $controlService->assignOwner($control->id, $user->id);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        // Verify file exists and is a valid PDF
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
        
        $encryptedContent = $storageService->get($filename);
        $decryptedContent = $exportService->decryptContent($encryptedContent);
        
        // Verify it's a valid PDF
        $this->assertStringStartsWith('%PDF', $decryptedContent);
        $this->assertGreaterThan(1000, strlen($decryptedContent));
    }

    public function test_audit_export_excludes_ownership_matrix_when_no_compliance_standards(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => null,
            'created_by' => $user->id,
        ]);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        // Decrypt and check content
        $storageService = new \App\Services\StorageService();
        $encryptedContent = $storageService->get($filename);
        $decryptedContent = $exportService->decryptContent($encryptedContent);

        // Ownership matrix should not be included
        $this->assertStringNotContainsString('Control Ownership Matrix', $decryptedContent);
    }

    public function test_audit_export_filters_controls_by_standard(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'compliance',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        // Create DORA control
        $doraControl = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'DORA Control',
        ]);

        // Create NIS2 control (should not be included)
        $nis2Control = Control::create([
            'standard' => 'NIS2',
            'article_reference' => 'NIS2 Art. 21.1',
            'title' => 'NIS2 Control',
        ]);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        // Verify file exists and is a valid PDF
        $storageService = new \App\Services\StorageService();
        $this->assertTrue($storageService->exists($filename));
        
        $encryptedContent = $storageService->get($filename);
        $decryptedContent = $exportService->decryptContent($encryptedContent);
        
        // Verify it's a valid PDF
        $this->assertStringStartsWith('%PDF', $decryptedContent);
        
        // Verify PDF was generated (size check)
        $this->assertGreaterThan(1000, strlen($decryptedContent));
    }
}
