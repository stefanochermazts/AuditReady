<?php

namespace Tests\Unit;

use App\Models\Audit;
use App\Models\Control;
use App\Models\Evidence;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create audit_control pivot table if not exists
        if (!Schema::hasTable('audit_control')) {
            Schema::create('audit_control', function ($table) {
                $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
                $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
                $table->timestamps();
                $table->primary(['audit_id', 'control_id']);
                $table->index('audit_id');
                $table->index('control_id');
            });
        }

        // Create evidences table if not exists, or add missing columns
        if (!Schema::hasTable('evidences')) {
            Schema::create('evidences', function ($table) {
                $table->id();
                $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
                $table->foreignId('evidence_request_id')->nullable();
                $table->foreignId('uploader_id')->constrained('users')->onDelete('cascade');
                $table->string('filename');
                $table->string('category')->nullable();
                $table->date('document_date')->nullable();
                $table->string('document_type')->nullable();
                $table->string('supplier')->nullable();
                $table->text('regulatory_reference')->nullable();
                $table->text('control_reference')->nullable();
                $table->string('mime_type')->nullable();
                $table->integer('size')->nullable();
                $table->string('stored_path')->nullable();
                $table->string('checksum')->nullable();
                $table->integer('version')->default(1);
                $table->text('encrypted_key')->nullable();
                $table->string('iv')->nullable();
                $table->enum('validation_status', ['pending', 'approved', 'rejected', 'needs_revision'])->default('pending');
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('validated_at')->nullable();
                $table->text('validation_notes')->nullable();
                $table->date('expiry_date')->nullable();
                $table->json('tags')->nullable();
                $table->text('notes')->nullable();
                $table->enum('confidentiality_level', ['public', 'internal', 'confidential', 'restricted'])->default('internal');
                $table->integer('retention_period_months')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add missing columns if table exists but columns are missing
            Schema::table('evidences', function ($table) {
                if (!Schema::hasColumn('evidences', 'category')) {
                    $table->string('category')->nullable()->after('filename');
                }
                if (!Schema::hasColumn('evidences', 'control_reference')) {
                    $table->text('control_reference')->nullable();
                }
                if (!Schema::hasColumn('evidences', 'validation_status')) {
                    $table->enum('validation_status', ['pending', 'approved', 'rejected', 'needs_revision'])->default('pending');
                }
            });
        }

        $this->auditService = app(AuditService::class);
    }

    public function test_get_graph_data_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA', 'NIS2'],
            'created_by' => $user->id,
        ]);

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

        // Attach controls to audit through pivot table
        $audit->controls()->syncWithoutDetaching([$control1->id, $control2->id]);

        $evidence1 = Evidence::create([
            'audit_id' => $audit->id,
            'uploader_id' => $user->id,
            'filename' => 'evidence1.pdf',
            'control_reference' => 'DORA Art. 8.1',
            'category' => 'policy',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'stored_path' => 'test/path1',
            'checksum' => 'checksum1',
            'encrypted_key' => 'key1',
            'iv' => 'iv1',
            'validation_status' => 'approved',
        ]);

        $evidence2 = Evidence::create([
            'audit_id' => $audit->id,
            'uploader_id' => $user->id,
            'filename' => 'evidence2.pdf',
            'control_reference' => 'NIS2 Art. 21.1',
            'category' => 'procedure',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'stored_path' => 'test/path2',
            'checksum' => 'checksum2',
            'encrypted_key' => 'key2',
            'iv' => 'iv2',
            'validation_status' => 'pending',
        ]);

        $graphData = $this->auditService->getGraphData($audit);

        // Assert structure
        $this->assertIsArray($graphData);
        $this->assertArrayHasKey('nodes', $graphData);
        $this->assertArrayHasKey('edges', $graphData);
        $this->assertIsArray($graphData['nodes']);
        $this->assertIsArray($graphData['edges']);

        // Assert audit node exists
        $auditNode = collect($graphData['nodes'])->firstWhere('id', "audit_{$audit->id}");
        $this->assertNotNull($auditNode);
        $this->assertEquals('audit', $auditNode['type']);
        $this->assertEquals('Test Audit', $auditNode['label']);

        // Assert control nodes exist
        $controlNodes = collect($graphData['nodes'])->where('type', 'control');
        $this->assertGreaterThanOrEqual(2, $controlNodes->count());

        // Assert evidence nodes exist
        $evidenceNodes = collect($graphData['nodes'])->where('type', 'evidence');
        $this->assertEquals(2, $evidenceNodes->count());

        // Assert edges
        $auditEdges = collect($graphData['edges'])->where('from', "audit_{$audit->id}");
        $this->assertGreaterThanOrEqual(4, $auditEdges->count()); // At least 2 controls + 2 evidences

        // Assert evidence-control edges
        $evidenceControlEdges = collect($graphData['edges'])
            ->where('type', 'evidence_control');
        $this->assertGreaterThanOrEqual(2, $evidenceControlEdges->count());
    }

    public function test_get_graph_data_handles_missing_control_references(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        // Create evidence with non-existent control reference
        $evidence = Evidence::create([
            'audit_id' => $audit->id,
            'uploader_id' => $user->id,
            'filename' => 'evidence.pdf',
            'control_reference' => 'NON_EXISTENT_REF',
            'category' => 'policy',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'stored_path' => 'test/path',
            'checksum' => 'checksum',
            'encrypted_key' => 'key',
            'iv' => 'iv',
        ]);

        // Should not throw exception
        $graphData = $this->auditService->getGraphData($audit);

        $this->assertIsArray($graphData);
        $this->assertArrayHasKey('nodes', $graphData);
        $this->assertArrayHasKey('edges', $graphData);

        // Evidence node should exist
        $evidenceNode = collect($graphData['nodes'])->firstWhere('id', "evidence_{$evidence->id}");
        $this->assertNotNull($evidenceNode);

        // But no edge to non-existent control
        $nonExistentEdges = collect($graphData['edges'])
            ->filter(function ($edge) use ($evidence) {
                return $edge['from'] === "evidence_{$evidence->id}"
                    && str_contains($edge['to'], 'NON_EXISTENT');
            });
        $this->assertCount(0, $nonExistentEdges);
    }

    public function test_get_graph_data_handles_truncation(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Large Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        // Create many controls and attach to audit (to test truncation)
        $controls = collect();
        for ($i = 1; $i <= 300; $i++) {
            $controls->push(Control::create([
                'standard' => 'DORA',
                'article_reference' => "DORA Art. {$i}",
                'title' => "Control {$i}",
            ]));
        }
        
        // Attach all controls to audit
        $audit->controls()->syncWithoutDetaching($controls->pluck('id'));

        // Create many evidences
        for ($i = 1; $i <= 250; $i++) {
            Evidence::create([
                'audit_id' => $audit->id,
                'uploader_id' => $user->id,
                'filename' => "evidence{$i}.pdf",
                'category' => 'policy',
                'mime_type' => 'application/pdf',
                'size' => 1024,
                'stored_path' => "test/path/{$i}",
                'checksum' => "checksum{$i}",
                'encrypted_key' => 'key',
                'iv' => 'iv',
            ]);
        }

        $graphData = $this->auditService->getGraphData($audit);

        // Should have truncated node
        $truncatedNode = collect($graphData['nodes'])->firstWhere('type', 'truncated');
        $this->assertNotNull($truncatedNode);

        // Total nodes should be <= 501 (500 + truncated node)
        $this->assertLessThanOrEqual(501, count($graphData['nodes']));
    }

    public function test_get_graph_data_with_empty_audit(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Empty Audit',
            'status' => 'draft',
            'audit_type' => 'internal',
            'compliance_standards' => [],
            'created_by' => $user->id,
        ]);

        $graphData = $this->auditService->getGraphData($audit);

        // Should have at least the audit node
        $this->assertGreaterThanOrEqual(1, count($graphData['nodes']));
        $auditNode = collect($graphData['nodes'])->firstWhere('type', 'audit');
        $this->assertNotNull($auditNode);

        // No edges if no controls or evidences
        $this->assertIsArray($graphData['edges']);
    }

    public function test_get_graph_data_matches_control_by_article_reference(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        $control = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Test Control',
        ]);

        // Attach control to audit through pivot table
        $audit->controls()->syncWithoutDetaching([$control->id]);

        // Evidence references control by article_reference
        $evidence = Evidence::create([
            'audit_id' => $audit->id,
            'uploader_id' => $user->id,
            'filename' => 'evidence.pdf',
            'control_reference' => 'DORA Art. 8.1', // Matches article_reference
            'category' => 'policy',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'stored_path' => 'test/path',
            'checksum' => 'checksum',
            'encrypted_key' => 'key',
            'iv' => 'iv',
        ]);

        $graphData = $this->auditService->getGraphData($audit);

        // Should have edge from evidence to control
        $evidenceControlEdge = collect($graphData['edges'])
            ->first(function ($edge) use ($evidence, $control) {
                return $edge['from'] === "evidence_{$evidence->id}"
                    && $edge['to'] === "control_{$control->id}"
                    && $edge['type'] === 'evidence_control';
            });

        $this->assertNotNull($evidenceControlEdge);
    }
}
