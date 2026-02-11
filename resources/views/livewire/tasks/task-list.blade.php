<?php
use App\Models\Task;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

return new #[Layout('layouts.app')] #[Title('Tasks')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public int $project_id;
    public int $perPage = 15;

    public function mount(int $project): void
    {
        $this->project_id = $project;
    }

    // PHP 8.4 property hook — prevent negative perPage
    // (declare as hook in PHP 8.4)
    public function setPerPage(int $value): void
    {
        $this->perPage = max(5, min(100, $value));
    }

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedStatus(): void   { $this->resetPage(); }
    public function updatedPriority(): void { $this->resetPage(); }

    public function sortBy(string $column): void
    {
        $this->sortDir = $this->sortBy === $column
            ? ($this->sortDir === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    #[Computed]
    public function tasks()
    {
        return Task::query()
            ->where('project_id', $this->project_id)
            ->when($this->search, fn($q) =>
                $q->where(fn($q) =>
                    $q->where('title', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%")
                )
            )
            ->when($this->status,   fn($q) => $q->where('status', $this->status))
            ->when($this->priority, fn($q) => $q->where('priority', $this->priority))
            ->with(['assignee', 'attachments'])
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
    }

    #[On('task-created')]
    #[On('task-updated')]
    #[On('task-deleted')]
    public function refresh(): void
    {
        unset($this->tasks);
    }
};?>


<div>
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-gray-900">Tasks</h2>
        <div class="flex gap-2">
            <a href="{{ route('projects.kanban', ['project' => $project_id]) }}" wire:navigate
               class="px-3 py-2 border rounded-xl text-sm hover:bg-gray-50">Kanban View</a>
            <livewire:tasks.task-form :project-id="$project_id" />
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="flex gap-3 mb-6">
        <div class="relative flex-1">
            <input wire:model.live.debounce.400ms="search"
                   type="text"
                   placeholder="Search tasks..."
                   class="w-full pl-10 pr-4 py-2 border rounded-lg bg-white" />
            {{-- v4: data-loading attribute — no JS needed --}}
            <div data-loading:class="opacity-100" data-loading:class.remove="opacity-0"
                 class="opacity-0 absolute right-3 top-2.5 transition">
                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>
        <select wire:model.live="status" class="border rounded-lg px-3 py-2 bg-white">
            <option value="">All Statuses</option>
            <option value="todo">Todo</option>
            <option value="in_progress">In Progress</option>
            <option value="review">Review</option>
            <option value="done">Done</option>
        </select>
        <select wire:model.live="priority" class="border rounded-lg px-3 py-2 bg-white">
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
        </select>
    </div>

    {{-- v4: data-loading:class for table fade --}}
    <div data-loading:class="opacity-50">
        <table class="w-full bg-white rounded-xl shadow-sm">
            <thead class="bg-gray-50 text-sm text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-100"
                        wire:click="sortBy('title')">
                        Title {{ $sortBy === 'title' ? ($sortDir === 'asc' ? '^' : 'v') : '' }}
                    </th>
                    <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-100"
                        wire:click="sortBy('status')">Status</th>
                    <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-100"
                        wire:click="sortBy('priority')">Priority</th>
                    <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-100"
                        wire:click="sortBy('due_date')">Due Date</th>
                    <th class="px-4 py-3 text-left">Assignee</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($this->tasks as $task)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <button wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })"
                                    class="font-medium text-blue-600 hover:underline text-left">
                                {{ $task->title }}
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-1 rounded-full text-xs font-medium',
                                'bg-green-100 text-green-700'  => $task->status === 'done',
                                'bg-blue-100 text-blue-700'    => $task->status === 'in_progress',
                                'bg-yellow-100 text-yellow-700'=> $task->status === 'review',
                                'bg-gray-100 text-gray-600'    => $task->status === 'todo',
                            ])>{{ $task->status_label }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-1 rounded-full text-xs font-medium',
                                'bg-red-100 text-red-700'      => $task->priority === 'critical',
                                'bg-orange-100 text-orange-700'=> $task->priority === 'high',
                                'bg-yellow-100 text-yellow-700'=> $task->priority === 'medium',
                                'bg-gray-100 text-gray-600'    => $task->priority === 'low',
                            ])>{{ ucfirst($task->priority) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $task->due_date?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $task->assignee?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-4 py-3">
                            {{-- v4: pass slot content to livewire component --}}
                            <livewire:tasks.task-form :task="$task" :key="'form-'.$task->id" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                            No tasks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->tasks->links() }}</div>

    <livewire:tasks.task-detail />
</div>


