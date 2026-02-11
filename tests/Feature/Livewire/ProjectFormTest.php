<?php

namespace Tests\Feature\Livewire;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('projects.project-form')
            ->set('name', 'My Project')
            ->set('status', 'active')
            ->call('save')
            ->assertDispatched('project-created');

        $this->assertDatabaseHas('projects', [
            'name' => 'My Project',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_edit_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        $this->actingAs($user);

        Livewire::test('projects.project-form', ['project' => $project])
            ->assertSet('isEditing', true)
            ->set('name', 'New Name')
            ->call('save')
            ->assertDispatched('project-updated');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New Name']);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('projects.project-form')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }
}
