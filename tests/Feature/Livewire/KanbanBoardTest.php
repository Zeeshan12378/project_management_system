<?php

namespace Tests\Feature\Livewire;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_reorder_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $taskA = Task::factory()->create(['project_id' => $project->id, 'order' => 0, 'status' => 'todo']);
        $taskB = Task::factory()->create(['project_id' => $project->id, 'order' => 1, 'status' => 'todo']);

        Livewire::test('tasks.kanban-board', ['project' => $project->id])
            ->call('reorder', $taskB->id, 0, 'todo');

        $this->assertDatabaseHas('tasks', ['id' => $taskB->id, 'order' => 0, 'status' => 'todo']);
        $this->assertDatabaseHas('tasks', ['id' => $taskA->id, 'order' => 1, 'status' => 'todo']);
    }

    public function test_can_move_task_between_columns_and_persist_order(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $todoTaskA = Task::factory()->create(['project_id' => $project->id, 'order' => 0, 'status' => 'todo']);
        $todoTaskB = Task::factory()->create(['project_id' => $project->id, 'order' => 1, 'status' => 'todo']);
        $progressTask = Task::factory()->create(['project_id' => $project->id, 'order' => 0, 'status' => 'in_progress']);

        // Simulate dropping todoTaskA into in_progress at index 1.
        Livewire::test('tasks.kanban-board', ['project' => $project->id])
            ->call('reorder', $todoTaskA->id, 1, 'in_progress');

        $this->assertDatabaseHas('tasks', ['id' => $progressTask->id, 'status' => 'in_progress', 'order' => 0]);
        $this->assertDatabaseHas('tasks', ['id' => $todoTaskA->id, 'status' => 'in_progress', 'order' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $todoTaskB->id, 'status' => 'todo', 'order' => 0]);
    }

    public function test_can_reorder_using_items_payload_path(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $taskA = Task::factory()->create(['project_id' => $project->id, 'order' => 0, 'status' => 'in_progress']);
        $taskB = Task::factory()->create(['project_id' => $project->id, 'order' => 1, 'status' => 'review']);

        Livewire::test('tasks.kanban-board', ['project' => $project->id])
            ->call('reorder', [
                ['value' => $taskB->id],
                ['value' => $taskA->id],
            ], 'review');

        $this->assertDatabaseHas('tasks', ['id' => $taskB->id, 'status' => 'review', 'order' => 0]);
        $this->assertDatabaseHas('tasks', ['id' => $taskA->id, 'status' => 'review', 'order' => 1]);
    }
}
