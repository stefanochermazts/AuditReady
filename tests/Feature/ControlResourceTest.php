<?php

namespace Tests\Feature;

use App\Models\Control;
use App\Models\ControlOwner;
use App\Models\User;
use App\Services\ControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with necessary permissions
        $this->user = User::factory()->create();
        
        // Create role and assign to user (simplified for tests)
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'Organization Owner',
            'guard_name' => 'web',
        ]);
        
        $this->user->assignRole($role);
        
        $this->actingAs($this->user);
    }

    public function test_can_list_controls(): void
    {
        Control::create([
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'Test Control',
        ]);

        $response = $this->get('/admin/controls');

        $response->assertStatus(200);
        $response->assertSee('Test Control');
    }

    public function test_can_create_control(): void
    {
        $response = $this->get('/admin/controls/create');

        $response->assertStatus(200);
    }

    public function test_can_store_control(): void
    {
        $data = [
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'New Control',
            'description' => 'Test Description',
            'category' => 'Governance',
        ];

        $response = $this->post('/admin/controls', $data);

        $this->assertDatabaseHas('controls', [
            'standard' => 'DORA',
            'article_reference' => 'DORA Art. 8.1',
            'title' => 'New Control',
        ]);
    }

    public function test_can_view_control(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $response = $this->get("/admin/controls/{$control->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Control');
    }

    public function test_can_edit_control(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $response = $this->get("/admin/controls/{$control->id}/edit");

        $response->assertStatus(200);
    }

    public function test_can_update_control(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Original Title',
        ]);

        $data = [
            'standard' => 'DORA',
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ];

        $response = $this->put("/admin/controls/{$control->id}", $data);

        $this->assertDatabaseHas('controls', [
            'id' => $control->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_control(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $response = $this->delete("/admin/controls/{$control->id}");

        $this->assertDatabaseMissing('controls', [
            'id' => $control->id,
        ]);
    }

    public function test_control_owners_relationship(): void
    {
        $control = Control::create([
            'standard' => 'DORA',
            'title' => 'Test Control',
        ]);

        $user = User::factory()->create();

        $controlService = app(ControlService::class);
        $controlService->assignOwner($control->id, $user->id, 'primary', 'CISO');

        $control->refresh();

        $this->assertTrue($control->hasOwners());
        $this->assertCount(1, $control->owners);
        $this->assertEquals($user->id, $control->owners->first()->id);
    }
}
