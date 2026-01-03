<?php

namespace Tests\Feature;

use App\Models\GapSnapshot;
use App\Models\Control;
use App\Models\User;
use App\Models\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class GapSnapshotResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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

        // Create role and permissions
        Role::firstOrCreate(['name' => 'Organization Owner', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-gap-snapshot', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-gap-snapshot', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update-gap-snapshot', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-gap-snapshot', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'viewAny-gap-snapshot', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Organization Owner');
        $this->user->givePermissionTo([
            'view-gap-snapshot', 'create-gap-snapshot', 'update-gap-snapshot', 
            'delete-gap-snapshot', 'viewAny-gap-snapshot'
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_list_gap_snapshots(): void
    {
        GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $response = $this->get('/admin/gap-snapshots');

        $response->assertStatus(200);
        $response->assertSee('Test Snapshot');
    }

    public function test_can_create_gap_snapshot(): void
    {
        $response = $this->get('/admin/gap-snapshots/create');

        $response->assertStatus(200);
    }

    public function test_can_store_gap_snapshot(): void
    {
        $data = [
            'name' => 'New Gap Snapshot',
            'standard' => 'DORA',
        ];

        $response = $this->post('/admin/gap-snapshots', $data);

        $this->assertDatabaseHas('gap_snapshots', [
            'name' => 'New Gap Snapshot',
            'standard' => 'DORA',
        ]);
    }

    public function test_can_view_gap_snapshot(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $response = $this->get("/admin/gap-snapshots/{$snapshot->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Snapshot');
    }

    public function test_can_edit_gap_snapshot(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $response = $this->get("/admin/gap-snapshots/{$snapshot->id}/edit");

        $response->assertStatus(200);
    }

    public function test_can_update_gap_snapshot(): void
    {
        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $data = [
            'name' => 'Updated Snapshot',
            'standard' => 'NIS2',
        ];

        $response = $this->put("/admin/gap-snapshots/{$snapshot->id}", $data);

        $this->assertDatabaseHas('gap_snapshots', [
            'id' => $snapshot->id,
            'name' => 'Updated Snapshot',
            'standard' => 'NIS2',
        ]);
    }

    public function test_can_link_snapshot_to_audit(): void
    {
        $audit = Audit::create([
            'name' => 'Test Audit',
            'created_by' => $this->user->id,
        ]);

        $snapshot = GapSnapshot::create([
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
        ]);

        $data = [
            'name' => 'Test Snapshot',
            'standard' => 'DORA',
            'audit_id' => $audit->id,
        ];

        $this->put("/admin/gap-snapshots/{$snapshot->id}", $data);

        $this->assertDatabaseHas('gap_snapshots', [
            'id' => $snapshot->id,
            'audit_id' => $audit->id,
        ]);
    }
}
