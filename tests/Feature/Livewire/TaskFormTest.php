<?php

namespace Tests\Feature\Livewire;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);
    }

    public function test_renders_correctly(): void
    {
        Livewire::test('tasks.task-form')->assertStatus(200);
    }

    public function test_can_create_task(): void
    {
        Livewire::test('tasks.task-form', ['projectId' => $this->project->id])
            ->call('openModal', projectId: $this->project->id)
            ->set('form.title', 'My New Task')
            ->set('form.priority', 'high')
            ->set('form.project_id', $this->project->id)
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('task-created')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('tasks', ['title' => 'My New Task', 'priority' => 'high']);
    }

    public function test_title_required(): void
    {
        Livewire::test('tasks.task-form')
            ->set('form.project_id', $this->project->id)
            ->call('save')
            ->assertHasErrors(['form.title' => 'required']);
    }

    public function test_title_min_length(): void
    {
        Livewire::test('tasks.task-form')
            ->set('form.title', 'AB')
            ->set('form.project_id', $this->project->id)
            ->call('save')
            ->assertHasErrors(['form.title' => 'min']);
    }

    public function test_can_edit_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Old Title',
        ]);

        Livewire::test('tasks.task-form', ['task' => $task])
            ->assertSet('isEditing', true)
            ->assertSet('form.title', 'Old Title')
            ->set('form.title', 'New Title')
            ->call('save')
            ->assertDispatched('task-updated');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'New Title']);
    }

    public function test_can_delete_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        Livewire::test('tasks.task-form', ['task' => $task])
            ->call('delete')
            ->assertDispatched('task-deleted');

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_cannot_delete_other_users_task(): void
    {
        $other = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $other->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        Livewire::test('tasks.task-form', ['task' => $task])
            ->call('delete')
            ->assertForbidden();
    }

    public function test_close_clears_errors(): void
    {
        Livewire::test('tasks.task-form')
            ->call('save')
            ->assertHasErrors('form.title')
            ->call('closeModal')
            ->assertHasNoErrors();
    }
}
