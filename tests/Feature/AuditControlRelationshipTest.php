<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Control;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditControlRelationshipTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_audit_can_attach_controls(): void
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

        $control2 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.2',
            'title' => 'Control 2',
        ]);

        // Attach controls
        $audit->controls()->attach([$control1->id, $control2->id]);

        // Assert relationship
        $this->assertCount(2, $audit->controls);
        $this->assertTrue($audit->controls->contains($control1));
        $this->assertTrue($audit->controls->contains($control2));

        // Assert pivot table
        $this->assertDatabaseHas('audit_control', [
            'audit_id' => $audit->id,
            'control_id' => $control1->id,
        ]);
        $this->assertDatabaseHas('audit_control', [
            'audit_id' => $audit->id,
            'control_id' => $control2->id,
        ]);
    }

    public function test_audit_can_detach_controls(): void
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

        $control2 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.2',
            'title' => 'Control 2',
        ]);

        // Attach controls
        $audit->controls()->attach([$control1->id, $control2->id]);
        $this->assertCount(2, $audit->controls);

        // Detach one control
        $audit->controls()->detach($control1->id);

        // Assert relationship
        $audit->refresh();
        $this->assertCount(1, $audit->controls);
        $this->assertFalse($audit->controls->contains($control1));
        $this->assertTrue($audit->controls->contains($control2));

        // Assert pivot table
        $this->assertDatabaseMissing('audit_control', [
            'audit_id' => $audit->id,
            'control_id' => $control1->id,
        ]);
        $this->assertDatabaseHas('audit_control', [
            'audit_id' => $audit->id,
            'control_id' => $control2->id,
        ]);
    }

    public function test_audit_can_sync_controls(): void
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

        $control2 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.2',
            'title' => 'Control 2',
        ]);

        $control3 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.3',
            'title' => 'Control 3',
        ]);

        // Attach initial controls
        $audit->controls()->attach([$control1->id, $control2->id]);

        // Sync to different set
        $audit->controls()->sync([$control2->id, $control3->id]);

        // Assert relationship
        $audit->refresh();
        $this->assertCount(2, $audit->controls);
        $this->assertFalse($audit->controls->contains($control1));
        $this->assertTrue($audit->controls->contains($control2));
        $this->assertTrue($audit->controls->contains($control3));
    }

    public function test_get_graph_data_includes_attached_controls(): void
    {
        $user = User::factory()->create();
        $auditService = app(AuditService::class);

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

        $control2 = Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.2',
            'title' => 'Control 2',
        ]);

        // Attach controls
        $audit->controls()->attach([$control1->id, $control2->id]);

        $graphData = $auditService->getGraphData($audit);

        // Assert control nodes exist
        $controlNodes = collect($graphData['nodes'])->where('type', 'control');
        $this->assertCount(2, $controlNodes);

        // Assert control node IDs
        $controlNodeIds = $controlNodes->pluck('id')->toArray();
        $this->assertContains("control_{$control1->id}", $controlNodeIds);
        $this->assertContains("control_{$control2->id}", $controlNodeIds);

        // Assert edges from audit to controls
        $auditControlEdges = collect($graphData['edges'])
            ->where('type', 'audit_control');
        $this->assertCount(2, $auditControlEdges);

        // Verify edges connect audit to both controls
        $edgeTargets = $auditControlEdges->pluck('to')->toArray();
        $this->assertContains("control_{$control1->id}", $edgeTargets);
        $this->assertContains("control_{$control2->id}", $edgeTargets);
    }

    public function test_control_can_access_audits(): void
    {
        $user = User::factory()->create();

        $audit1 = Audit::create([
            'name' => 'Audit 1',
            'status' => 'in_progress',
            'audit_type' => 'internal',
            'compliance_standards' => ['DORA'],
            'created_by' => $user->id,
        ]);

        $audit2 = Audit::create([
            'name' => 'Audit 2',
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

        // Attach control to audits
        $audit1->controls()->attach($control->id);
        $audit2->controls()->attach($control->id);

        // Assert inverse relationship
        $this->assertCount(2, $control->audits);
        $this->assertTrue($control->audits->contains($audit1));
        $this->assertTrue($control->audits->contains($audit2));
    }

    public function test_duplicate_attach_prevents_duplicates(): void
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

        // Attach control first time
        $audit->controls()->attach($control->id);
        $this->assertCount(1, $audit->controls);

        // Try to attach again - should throw exception due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        $audit->controls()->attach($control->id);

        // Alternative: use syncWithoutDetaching which handles duplicates gracefully
        // $audit->controls()->syncWithoutDetaching([$control->id]);
        // $audit->refresh();
        // $this->assertCount(1, $audit->controls);
    }
}
