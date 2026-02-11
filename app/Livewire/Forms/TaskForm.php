<?php

namespace App\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class TaskForm extends Form
{
    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public ?string $description = null;

    #[Validate('required|in:todo,in_progress,review,done')]
    public string $status = 'todo';

    #[Validate('required|in:low,medium,high,critical')]
    public string $priority = 'medium';

    #[Validate('nullable|exists:users,id')]
    public ?int $assigned_to = null;

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    #[Validate('required|exists:projects,id')]
    public int $project_id = 0;

    public function fill($task): void
    {
        $this->title       = $task->title;
        $this->description = $task->description;
        $this->status      = $task->status;
        $this->priority    = $task->priority;
        $this->assigned_to = $task->assigned_to;
        $this->due_date    = $task->due_date?->format('Y-m-d');
        $this->project_id  = $task->project_id;
    }

    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'assigned_to' => $this->assigned_to,
            'due_date'    => $this->due_date,
            'project_id'  => $this->project_id,
        ];
    }
}
