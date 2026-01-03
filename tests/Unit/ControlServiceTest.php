<?php

namespace Tests\Unit;

use App\Models\Control;
use App\Models\ControlOwner;
use App\Models\User;
use App\Services\ControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ControlServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ControlService $controlService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controlService = app(ControlService::class);
    }

    public function test_import_standard_controls(): void
    {
        $controls = [
            [
                'article_reference' => 'DORA Art. 8.1',
                'title' => 'Test Control 1',
                'description' => 'Test Description 1',
                'category' => 'Governance',
            ],
            [
                'article_reference' => 'DORA Art. 8.2',
                'title' => 'Test Control 2',
                'description' => 'Test Description 2',
                'category' => 'Governance',
            ],
        ];

        $imported = $this->controlService->importStandardControls($controls, 'DORA');

        $this->assertEquals(2, $imported);
        $this->assertDatabaseHas('controls', [
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Test Control 1',
        ]);
        $this->assertDatabaseHas('controls', [
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.2',
            'title' => 'Test Control 2',
        ]);
    }

    public function test_import_skips_duplicate_controls(): void
    {
        // Create existing control
        Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Existing Control',
        ]);

        $controls = [
            [
                'article_reference' => 'DORA Art. 8.1', // Duplicate
                'title' => 'Test Control 1',
            ],
            [
                'article_reference' => 'DORA Art. 8.2', // New
                'title' => 'Test Control 2',
            ],
        ];

        $imported = $this->controlService->importStandardControls($controls, 'DORA');

        $this->assertEquals(1, $imported); // Only new one imported
        $this->assertDatabaseHas('controls', [
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Existing Control', // Original title preserved
        ]);
    }

    public function test_assign_owner(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $user = User::factory()->create();

        $controlOwner = $this->controlService->assignOwner(
            $control->id,
            $user->id,
            'primary',
            'CISO',
            'Test notes'
        );

        $this->assertInstanceOf(ControlOwner::class, $controlOwner);
        $this->assertDatabaseHas('control_owners', [
            'control_id' => $control->id,
            'user_id' => $user->id,
            'responsibility_level' => 'primary',
            'role_name' => 'CISO',
            'notes' => 'Test notes',
        ]);
    }

    public function test_assign_owner_updates_existing(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $user = User::factory()->create();

        // First assignment
        $this->controlService->assignOwner(
            $control->id,
            $user->id,
            'primary',
            'CISO'
        );

        // Update assignment
        $controlOwner = $this->controlService->assignOwner(
            $control->id,
            $user->id,
            'secondary',
            'IT Manager',
            'Updated notes'
        );

        $this->assertDatabaseHas('control_owners', [
            'control_id' => $control->id,
            'user_id' => $user->id,
            'responsibility_level' => 'secondary',
            'role_name' => 'IT Manager',
            'notes' => 'Updated notes',
        ]);

        // Should only have one record
        $this->assertEquals(1, ControlOwner::where('control_id', $control->id)->count());
    }

    public function test_remove_owner(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $user = User::factory()->create();

        // Assign owner
        $this->controlService->assignOwner($control->id, $user->id);

        // Remove owner
        $removed = $this->controlService->removeOwner($control->id, $user->id);

        $this->assertTrue($removed);
        $this->assertDatabaseMissing('control_owners', [
            'control_id' => $control->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_get_ownership_matrix(): void
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

        // Assign owner to control1
        $this->controlService->assignOwner($control1->id, $user->id, 'primary', 'CISO');

        $matrix = $this->controlService->getOwnershipMatrix();

        $this->assertCount(2, $matrix);
        $this->assertEquals('DORA Art. 8.1', $matrix[0]['article_reference']);
        $this->assertTrue($matrix[0]['has_owners']);
        $this->assertCount(1, $matrix[0]['owners']);
        $this->assertEquals($user->name, $matrix[0]['owners'][0]['user_name']);
    }

    public function test_get_ownership_matrix_with_filters(): void
    {
        Control::create([
            'standard' => 'DORA',
            'title' => 'DORA Control',
        ]);

        Control::create([
            'standard' => 'NIS2',
            'title' => 'NIS2 Control',
        ]);

        $matrix = $this->controlService->getOwnershipMatrix(['standard' => 'DORA']);

        $this->assertCount(1, $matrix);
        $this->assertEquals('DORA', $matrix[0]['standard']);
    }

    public function test_get_controls_without_owner(): void
    {
        $control1 = Control::create([
            'standard' => 'DORA',
            'title' => 'Control 1',
        ]);

        $control2 = Control::create([
            'standard' => 'DORA',
            'title' => 'Control 2',
        ]);

        $user = User::factory()->create();

        // Assign owner to control1 only
        $this->controlService->assignOwner($control1->id, $user->id);

        $controlsWithoutOwner = $this->controlService->getControlsWithoutOwner();

        $this->assertCount(1, $controlsWithoutOwner);
        $this->assertEquals($control2->id, $controlsWithoutOwner->first()->id);
    }

    public function test_get_ownership_statistics(): void
    {
        $control1 = Control::create(['standard' => 'DORA', 'title' => 'Control 1']);
        $control2 = Control::create(['standard' => 'DORA', 'title' => 'Control 2']);
        $control3 = Control::create(['standard' => 'NIS2', 'title' => 'Control 3']);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Assign owners
        $this->controlService->assignOwner($control1->id, $user1->id, 'primary');
        $this->controlService->assignOwner($control1->id, $user2->id, 'secondary');
        $this->controlService->assignOwner($control2->id, $user1->id, 'primary');

        $stats = $this->controlService->getOwnershipStatistics();

        $this->assertEquals(3, $stats['total_controls']);
        $this->assertEquals(2, $stats['controls_with_owners']);
        $this->assertEquals(1, $stats['controls_without_owners']);
        $this->assertEquals(3, $stats['total_assignments']);
        $this->assertEquals(2, $stats['primary_owners']);
        $this->assertEquals(1, $stats['secondary_owners']);
        $this->assertGreaterThan(0, $stats['coverage_percentage']);
    }
}
