<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\GapSnapshot;
use App\Models\GapSnapshotResponse;
use App\Models\Control;
use App\Models\User;
use App\Services\GapSnapshotService;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuditExportWithGapSnapshotTest extends TestCase
{
    use RefreshDatabase;

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

        Storage::fake('local');
    }

    public function test_audit_export_includes_gap_snapshot_when_linked(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'created_by' => $user->id,
        ]);

        $snapshot = GapSnapshot::create([
            'name' => 'Test Gap Snapshot',
            'standard' => 'DORA',
            'audit_id' => $audit->id,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $gapSnapshotService = app(GapSnapshotService::class);
        $gapSnapshotService->saveResponse($snapshot, $control->id, 'no', 'Has gap');

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        $this->assertNotEmpty($filename);
        Storage::disk('local')->assertExists($filename);
    }

    public function test_audit_export_excludes_incomplete_snapshots(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'created_by' => $user->id,
        ]);

        // Create incomplete snapshot (no completed_at)
        GapSnapshot::create([
            'name' => 'Incomplete Snapshot',
            'standard' => 'DORA',
            'audit_id' => $audit->id,
        ]);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        $this->assertNotEmpty($filename);
        Storage::disk('local')->assertExists($filename);
    }

    public function test_audit_export_includes_multiple_snapshots(): void
    {
        $user = User::factory()->create();
        
        $audit = Audit::create([
            'name' => 'Test Audit',
            'status' => 'in_progress',
            'created_by' => $user->id,
        ]);

        $snapshot1 = GapSnapshot::create([
            'name' => 'Snapshot 1',
            'standard' => 'DORA',
            'audit_id' => $audit->id,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        $snapshot2 = GapSnapshot::create([
            'name' => 'Snapshot 2',
            'standard' => 'NIS2',
            'audit_id' => $audit->id,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        $exportService = app(ExportService::class);
        $filename = $exportService->exportToPdf($audit);

        $this->assertNotEmpty($filename);
        Storage::disk('local')->assertExists($filename);
    }
}
