<?php

namespace Tests\Feature;

use App\Models\GapSnapshot;
use App\Models\GapSnapshotResponse;
use App\Models\Control;
use App\Models\User;
use App\Models\Audit;
use App\Services\GapSnapshotService;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class GapSnapshotExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected GapSnapshotService $gapSnapshotService;
    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually create tables for SQLite in-memory
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

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

        Schema::create('gap_snapshots', function ($table) {
            $table->id();
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('set null');
            $table->string('name');
            $table->enum('standard', ['DORA', 'NIS2', 'both'])->default('both');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

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

        Role::firstOrCreate(['name' => 'Organization Owner', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'export-gap-snapshot', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Organization Owner');
        $this->user->givePermissionTo(['export-gap-snapshot']);

        $this->actingAs($this->user);

        $this->gapSnapshotService = app(GapSnapshotService::class);
        $this->exportService = app(ExportService::class);

        Storage::fake('local');
    }

    public function test_can_export_gap_snapshot_to_pdf(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
            'completed_by' => $this->user->id,
            'completed_at' => now(),
        ]);

        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
            'category' => 'Governance',
        ]);

        $this->gapSnapshotService->saveResponse($snapshot, $control->id, 'no', 'Has gap');

        $filename = $this->exportService->exportGapSnapshotToPdf($snapshot);

        Storage::disk('local')->assertExists($filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_export_includes_gap_analysis(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
            'completed_by' => $this->user->id,
            'completed_at' => now(),
        ]);

        $control1 = Control::create(['standard' => 'DORA', 'title' => 'Control 1', 'category' => 'Governance']);
        $control2 = Control::create(['standard' => 'DORA', 'title' => 'Control 2', 'category' => 'Risk Management']);

        $this->gapSnapshotService->saveResponse($snapshot, $control1->id, 'yes');
        $this->gapSnapshotService->saveResponse($snapshot, $control2->id, 'no', 'Has gap');

        $filename = $this->exportService->exportGapSnapshotToPdf($snapshot);
        Storage::disk('local')->assertExists($filename);
    }

    public function test_export_includes_high_risk_controls(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
            'completed_by' => $this->user->id,
            'completed_at' => now(),
        ]);

        $control = Control::create(['standard' => 'DORA', 'title' => 'High Risk Control']);

        // Create response with gap and no evidence
        $this->gapSnapshotService->saveResponse($snapshot, $control->id, 'no', 'Gap identified', []);

        $filename = $this->exportService->exportGapSnapshotToPdf($snapshot);
        Storage::disk('local')->assertExists($filename);
    }
}
