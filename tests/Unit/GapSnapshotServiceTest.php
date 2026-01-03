<?php

namespace Tests\Unit;

use App\Models\GapSnapshot;
use App\Models\GapSnapshotResponse;
use App\Models\Control;
use App\Models\User;
use App\Models\Audit;
use App\Services\GapSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GapSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GapSnapshotService $gapSnapshotService;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually create tables for SQLite in-memory
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audits')) {
            Schema::create('audits', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'closed', 'archived'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('closed_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('controls')) {
            Schema::create('controls', function ($table) {
            $table->id();
            $table->enum('standard', ['DORA', 'NIS2', 'ISO27001', 'custom'])->default('custom');
            $table->string('article_reference')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gap_snapshots')) {
            Schema::create('gap_snapshots', function ($table) {
            $table->id();
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('set null');
            $table->string('name');
            $table->enum('standard', ['DORA', 'NIS2', 'both'])->default('both');
                $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gap_snapshot_responses')) {
            Schema::create('gap_snapshot_responses', function ($table) {
            $table->id();
            $table->foreignId('gap_snapshot_id')->constrained('gap_snapshots')->onDelete('cascade');
            $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
            $table->enum('response', ['yes', 'no', 'partial', 'not_applicable'])->default('no');
            $table->text('notes')->nullable();
            $table->json('evidence_ids')->nullable();
            $table->timestamps();
                $table->unique(['gap_snapshot_id', 'control_id']);
            });
        }

        $this->gapSnapshotService = app(GapSnapshotService::class);
    }

    public function test_can_create_snapshot(): void
    {
        $data = [
            'name' => 'Test Gap Snapshot',
            'standard' => 'DORA',
        ];

        $snapshot = $this->gapSnapshotService->createSnapshot($data);

        $this->assertInstanceOf(GapSnapshot::class, $snapshot);
        $this->assertEquals('Test Gap Snapshot', $snapshot->name);
        $this->assertEquals('DORA', $snapshot->standard);
        $this->assertDatabaseHas('gap_snapshots', [
            'name' => 'Test Gap Snapshot',
            'standard' => 'DORA',
        ]);
    }

    public function test_can_save_response(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $response = $this->gapSnapshotService->saveResponse(
            $snapshot,
            $control->id,
            'yes',
            'Test notes',
            [1, 2, 3]
        );

        $this->assertInstanceOf(GapSnapshotResponse::class, $response);
        $this->assertEquals('yes', $response->response);
        $this->assertEquals('Test notes', $response->notes);
        $this->assertEquals([1, 2, 3], $response->evidence_ids);
        $this->assertDatabaseHas('gap_snapshot_responses', [
            'gap_snapshot_id' => $snapshot->id,
            'control_id' => $control->id,
            'response' => 'yes',
        ]);
    }

    public function test_can_update_existing_response(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        // Create initial response
        $this->gapSnapshotService->saveResponse($snapshot, $control->id, 'no', 'Initial notes');

        // Update response
        $response = $this->gapSnapshotService->saveResponse(
            $snapshot,
            $control->id,
            'yes',
            'Updated notes'
        );

        $this->assertEquals('yes', $response->response);
        $this->assertEquals('Updated notes', $response->notes);
        
        // Should only have one response
        $this->assertEquals(1, GapSnapshotResponse::where('gap_snapshot_id', $snapshot->id)->count());
    }

    public function test_can_complete_snapshot(): void
    {
        $user = User::factory()->create();
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $completed = $this->gapSnapshotService->completeSnapshot($snapshot, $user->id);

        $this->assertNotNull($completed->completed_at);
        $this->assertEquals($user->id, $completed->completed_by);
    }

    public function test_get_gap_analysis(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $control1 = Control::create([
            'standard' => 'DORA',
            'title' => 'Control 1',
            'category' => 'Governance',
        ]);

        $control2 = Control::create([
            'standard' => 'DORA',
            'title' => 'Control 2',
            'category' => 'Risk Management',
        ]);

        // Create responses
        $this->gapSnapshotService->saveResponse($snapshot, $control1->id, 'yes');
        $this->gapSnapshotService->saveResponse($snapshot, $control2->id, 'no', 'Has gap');

        $analysis = $this->gapSnapshotService->getGapAnalysis($snapshot);

        $this->assertArrayHasKey('total_controls', $analysis);
        $this->assertArrayHasKey('answered_controls', $analysis);
        $this->assertArrayHasKey('gaps_count', $analysis);
        $this->assertArrayHasKey('gaps', $analysis);
        $this->assertEquals(2, $analysis['total_controls']);
        $this->assertEquals(2, $analysis['answered_controls']);
        $this->assertEquals(1, $analysis['gaps_count']);
        $this->assertIsArray($analysis['gaps']);
    }

    public function test_get_statistics(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $control1 = Control::create(['standard' => 'DORA', 'title' => 'Control 1']);
        $control2 = Control::create(['standard' => 'DORA', 'title' => 'Control 2']);

        $this->gapSnapshotService->saveResponse($snapshot, $control1->id, 'yes');
        $this->gapSnapshotService->saveResponse($snapshot, $control2->id, 'no');

        $statistics = $this->gapSnapshotService->getStatistics($snapshot);

        $this->assertArrayHasKey('completion_percentage', $statistics);
        $this->assertArrayHasKey('yes_count', $statistics);
        $this->assertArrayHasKey('no_count', $statistics);
        $this->assertEquals(1, $statistics['yes_count']);
        $this->assertEquals(1, $statistics['no_count']);
        $this->assertEquals(1, $statistics['gaps_count']);
    }

    public function test_get_controls_for_standard(): void
    {
        Control::create(['standard' => 'DORA', 'title' => 'DORA Control 1']);
        Control::create(['standard' => 'DORA', 'title' => 'DORA Control 2']);
        Control::create(['standard' => 'NIS2', 'title' => 'NIS2 Control 1']);

        $doraControls = $this->gapSnapshotService->getControlsForStandard('DORA');
        $this->assertEquals(2, $doraControls->count());

        $nis2Controls = $this->gapSnapshotService->getControlsForStandard('NIS2');
        $this->assertEquals(1, $nis2Controls->count());
    }
}
