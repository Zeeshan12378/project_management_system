<?php
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

return new #[Layout('layouts.app')] #[Title('Projects')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    #[Computed]
    public function projects()
    {
        return Project::query()
            ->where('user_id', auth()->id())
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) =>
                $q->where('status', $this->status))
            ->withCount('tasks')
            ->latest()
            ->paginate(12);
    }

    #[On('project-created')]
    #[On('project-updated')]
    #[On('project-deleted')]
    public function refresh(): void { unset($this->projects); }
};
?>


<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <p class="text-gray-500 text-sm mt-0.5">Manage all your projects</p>
        </div>
        <livewire:projects.project-form />
    </div>

    <div class="flex gap-3 mb-6">
        <input wire:model.live.debounce.400ms="search"
               type="text"
               placeholder="Search projects..."
               class="flex-1 border rounded-xl px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-400" />
        <select wire:model.live="status" class="border rounded-xl px-3 py-2 text-sm bg-white">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($this->projects as $project)
            <div class="bg-white rounded-2xl p-5 shadow-sm border hover:shadow-md transition group">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-semibold text-gray-900">{{ $project->name }}</h3>
                    <span @class([
                        'text-xs px-2 py-0.5 rounded-full font-medium',
                        'bg-green-100 text-green-700'  => $project->status === 'active',
                        'bg-gray-100 text-gray-600'    => $project->status === 'archived',
                        'bg-blue-100 text-blue-700'    => $project->status === 'completed',
                    ])>{{ ucfirst($project->status) }}</span>
                </div>
                <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $project->description ?? 'No description.' }}</p>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>{{ $project->tasks_count }} tasks</span>
                    <span>{{ $project->deadline?->format('M d, Y') ?? 'No deadline' }}</span>
                </div>
                <div class="flex gap-2 mt-4 opacity-0 group-hover:opacity-100 transition">
                    <a href="{{ route('projects.kanban', $project) }}" wire:navigate
                       class="flex-1 text-center py-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs hover:bg-blue-100">
                        Kanban
                    </a>
                    <a href="{{ route('projects.tasks', $project) }}" wire:navigate
                       class="flex-1 text-center py-1.5 bg-gray-50 text-gray-600 rounded-lg text-xs hover:bg-gray-100">
                        Task List
                    </a>
                    <livewire:projects.project-form :project="$project" :key="'pf-'.$project->id" />
                </div>
            </div>
        @empty
            <div class="col-span-3 py-20 text-center text-gray-400">
                <p class="text-4xl mb-3">No projects yet</p>
                <p class="text-sm mt-1">Create your first project to get started</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $this->projects->links() }}</div>
</div>


