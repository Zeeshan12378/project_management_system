<?php
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

return new #[Layout('layouts.app')] #[Title('Kanban Board')] class extends Component {
    public int $project_id;

    public array $columns = [
        'todo'        => ['label' => 'To Do',       'color' => 'blue'],
        'in_progress' => ['label' => 'In Progress',  'color' => 'yellow'],
        'review'      => ['label' => 'In Review',    'color' => 'purple'],
        'done'        => ['label' => 'Done',          'color' => 'green'],
    ];

    public function mount(int $project): void
    {
        $this->project_id = $project;
    }

    // Accepts both:
    // - wire:sort callback: reorder($item, $position, $status)
    // - x-on:sort callback: reorder($event.detail.items, $status)
    public function reorder(mixed $arg1 = null, mixed $arg2 = null, mixed $arg3 = null): void
    {
        if (is_array($arg1)) {
            $this->reorderByItems($arg1, (string) $arg2);
            return;
        }

        $taskId = is_numeric($arg1) ? (int) $arg1 : null;
        $position = is_numeric($arg2) ? (int) $arg2 : null;
        $toStatus = is_string($arg3) ? $arg3 : '';

        if (! $taskId || ! array_key_exists($toStatus, $this->columns)) {
            return;
        }

        DB::transaction(function () use ($taskId, $position, $toStatus) {
            $task = Task::query()
                ->where('project_id', $this->project_id)
                ->find($taskId);

            if (! $task) {
                return;
            }

            $fromStatus = $task->status;

            if ($fromStatus !== $toStatus) {
                $task->update(['status' => $toStatus]);
            }

            $this->resequenceColumn($toStatus, (int) $taskId, $position);

            if ($fromStatus !== $toStatus) {
                $this->resequenceColumn($fromStatus);
            }
        });

        $this->dispatch('toast', message: 'Board updated!', type: 'info');
    }

    private function reorderByItems(array $items, string $toStatus): void
    {
        if (! array_key_exists($toStatus, $this->columns)) {
            return;
        }

        $ids = collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                return isset($item['value']) && is_numeric($item['value'])
                    ? (int) $item['value']
                    : null;
            })
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids, $toStatus) {
            $tasks = Task::query()
                ->where('project_id', $this->project_id)
                ->whereIn('id', $ids->all())
                ->get()
                ->keyBy('id');

            $fromStatuses = $tasks->pluck('status')->unique()->all();

            foreach ($ids as $index => $taskId) {
                if (! isset($tasks[$taskId])) {
                    continue;
                }

                $tasks[$taskId]->update([
                    'status' => $toStatus,
                    'order' => $index,
                ]);
            }

            $this->resequenceColumn($toStatus);

            foreach ($fromStatuses as $fromStatus) {
                if ($fromStatus !== $toStatus) {
                    $this->resequenceColumn($fromStatus);
                }
            }
        });
    }

    private function resequenceColumn(string $status, ?int $focusTaskId = null, ?int $focusPosition = null): void
    {
        $ids = Task::query()
            ->where('project_id', $this->project_id)
            ->where('status', $status)
            ->when($focusTaskId, fn ($query) => $query->where('id', '!=', $focusTaskId))
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($focusTaskId !== null) {
            $targetPosition = max(0, min($focusPosition ?? count($ids), count($ids)));
            array_splice($ids, $targetPosition, 0, [$focusTaskId]);
        }

        foreach ($ids as $index => $id) {
            Task::whereKey($id)->update(['order' => $index]);
        }
    }

    // Called when card is dragged between columns
    public function moveToColumn(int $taskId, string $newStatus): void
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('update', $task);
        $task->update(['status' => $newStatus]);
        $this->dispatch('task-updated', taskId: $taskId);
    }

    #[On('task-created')]
    #[On('task-updated')]
    #[On('task-deleted')]
    public function refresh(): void {}

    public function with(): array
    {
        $tasksByStatus = Task::where('project_id', $this->project_id)
            ->with('assignee')
            ->orderBy('order')
            ->get()
            ->groupBy('status');

        return compact('tasksByStatus');
    }
};
?>


<div>
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-gray-900">Kanban Board</h2>
        <div class="flex gap-2">
            <a href="{{ route('projects.tasks', ['project' => $project_id]) }}" wire:navigate
               class="px-3 py-2 border rounded-xl text-sm hover:bg-gray-50">List View</a>
            <livewire:tasks.task-form :project-id="$project_id" />
        </div>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4">
    @foreach($columns as $status => $column)
        <div class="flex-shrink-0 w-72 bg-gray-100 rounded-2xl p-3">
            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex items-center gap-2">
                    <div @class([
                        'w-2 h-2 rounded-full',
                        'bg-blue-500'   => $column['color'] === 'blue',
                        'bg-yellow-500' => $column['color'] === 'yellow',
                        'bg-purple-500' => $column['color'] === 'purple',
                        'bg-green-500'  => $column['color'] === 'green',
                    ])></div>
                    <h3 class="font-semibold text-sm text-gray-700">{{ $column['label'] }}</h3>
                </div>
                <span class="text-xs bg-white border rounded-full px-2 py-0.5 text-gray-500">
                    {{ $tasksByStatus->get($status)?->count() ?? 0 }}
                </span>
            </div>

            {{-- v4: wire:sort handles drag-and-drop natively --}}
            <div wire:sort="reorder($item, $position, '{{ $status }}')"
                 wire:sort:group="kanban-{{ $project_id }}"
                 x-on:sort="$wire.reorder($event.detail.items, '{{ $status }}')"
                 class="space-y-2 min-h-[100px]">

                @foreach($tasksByStatus->get($status, collect()) as $task)
                    <div wire:sort:item="{{ $task->id }}"
                         wire:key="task-{{ $task->id }}"
                         class="bg-white rounded-xl p-3 shadow-sm border border-transparent hover:border-blue-200 transition group">

                        {{-- Drag handle --}}
                        <div wire:sort:handle class="drag-handle flex items-start justify-between mb-2 cursor-grab active:cursor-grabbing">
                            <p class="text-sm font-medium text-gray-800 leading-snug flex-1">{{ $task->title }}</p>
                            <svg class="w-4 h-4 text-gray-300 ml-2 flex-shrink-0 opacity-0 group-hover:opacity-100" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                            </svg>
                        </div>

                        <div class="flex items-center justify-between">
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full font-medium',
                                'bg-red-100 text-red-600'    => $task->priority === 'critical',
                                'bg-orange-100 text-orange-600' => $task->priority === 'high',
                                'bg-yellow-100 text-yellow-600' => $task->priority === 'medium',
                                'bg-gray-100 text-gray-500'   => $task->priority === 'low',
                            ])>{{ ucfirst($task->priority) }}</span>
                            <button wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })"
                                    class="text-xs text-gray-400 hover:text-blue-500">View</button>
                        </div>

                        @if($task->assignee)
                            <p class="text-xs text-gray-400 mt-1.5">Assignee: {{ $task->assignee->name }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <button wire:click="$dispatch('open-task-form', { projectId: {{ $project_id }}, status: '{{ $status }}' })"
                    class="mt-2 w-full text-sm text-gray-400 hover:text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition">
                + Add Task
            </button>
        </div>
    @endforeach
    </div>

    <livewire:tasks.task-detail />
</div>
