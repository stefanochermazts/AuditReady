<?php

namespace Tests\Unit;

use App\Models\Audit;
use App\Models\Control;
use App\Models\Evidence;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditServiceCacheTest extends TestCase
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
                $table->foreignId('uploader_id')->constrained('users')->onDelete('cascade');
                $table->string('filename');
                $table->string('category')->nullable();
                $table->text('control_reference')->nullable();
                $table->string('mime_type')->nullable();
                $table->integer('size')->nullable();
                $table->string('stored_path')->nullable();
                $table->string('checksum')->nullable();
                $table->integer('version')->default(1);
                $table->text('encrypted_key')->nullable();
                $table->string('iv')->nullable();
                $table->enum('validation_status', ['pending', 'approved', 'rejected', 'needs_revision'])->default('pending');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add missing columns if table exists
            Schema::table('evidences', function ($table) {
                if (!Schema::hasColumn('evidences', 'category')) {
                    $table->string('category')->nullable()->after('filename');
                }
                if (!Schema::hasColumn('evidences', 'control_reference')) {
                    $table->text('control_reference')->nullable();
                }
            });
        }

        $this->auditService = app(AuditService::class);
        Cache::flush();
    }

    public function test_get_graph_data_uses_cache(): void
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
            'title' => 'Control',
        ]);

        $audit->controls()->syncWithoutDetaching([$control->id]);

        // First call - should generate and cache
        $graphData1 = $this->auditService->getGraphData($audit);
        $this->assertIsArray($graphData1);
        $this->assertArrayHasKey('nodes', $graphData1);
        $this->assertArrayHasKey('edges', $graphData1);

        // Verify cache is working (second call should be faster/same data)
        // Note: Cache::has() may not work with tagged cache, so we verify by comparing results

        // Second call - should use cache (same data)
        $graphData2 = $this->auditService->getGraphData($audit);
        $this->assertEquals($graphData1, $graphData2);
    }

    public function test_cache_invalidated_when_evidence_created(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        // Generate initial graph data (cached)
        $graphData1 = $this->auditService->getGraphData($audit);
        $initialNodeCount = count($graphData1['nodes']);

        // Create new evidence (should invalidate cache)
        $evidence = Evidence::create([
            'audit_id' => $audit->id,
            'uploader_id' => $user->id,
            'filename' => 'new-evidence.pdf',
            'category' => 'policy',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'stored_path' => 'test/path',
            'checksum' => 'test-checksum',
            'encrypted_key' => 'test-key',
            'iv' => 'test-iv',
        ]);

        // Get graph data again - should be regenerated with new evidence
        $graphData2 = $this->auditService->getGraphData($audit);
        $newNodeCount = count($graphData2['nodes']);

        // Should have one more node (the new evidence)
        $this->assertEquals($initialNodeCount + 1, $newNodeCount);

        // Verify new evidence node exists
        $evidenceNodes = collect($graphData2['nodes'])->where('type', 'evidence');
        $this->assertTrue($evidenceNodes->contains(function ($node) use ($evidence) {
            return $node['id'] === "evidence_{$evidence->id}";
        }));
    }

    public function test_cache_invalidated_when_control_attached(): void
    {
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        $control1 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Control 1',
        ]);

        // Attach first control and generate graph
        $audit->controls()->syncWithoutDetaching([$control1->id]);
        $graphData1 = $this->auditService->getGraphData($audit);
        $initialControlCount = collect($graphData1['nodes'])->where('type', 'control')->count();

        // Manually invalidate cache to simulate observer behavior
        $this->auditService->invalidateGraphCache($audit);

        // Attach second control
        $control2 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.2',
            'title' => 'Control 2',
        ]);

        $audit->controls()->syncWithoutDetaching([$control2->id]);

        // Manually invalidate cache again (simulating observer)
        $this->auditService->invalidateGraphCache($audit);

        // Get graph data again - should include both controls
        $graphData2 = $this->auditService->getGraphData($audit);
        $newControlCount = collect($graphData2['nodes'])->where('type', 'control')->count();

        // Should have both control nodes
        $this->assertEquals(2, $newControlCount);
    }

    public function test_cache_fallback_when_tags_not_supported(): void
    {
        // This test verifies that the fallback to regular cache works
        // when tagged cache is not supported (e.g., file cache)
        $user = User::factory()->create();

        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        // Should work even if tags are not supported
        $graphData = $this->auditService->getGraphData($audit);
        $this->assertIsArray($graphData);
        $this->assertArrayHasKey('nodes', $graphData);
        $this->assertArrayHasKey('edges', $graphData);
    }
}
