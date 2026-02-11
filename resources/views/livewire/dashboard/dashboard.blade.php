<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

return new #[Layout('layouts.app')] #[Title('Dashboard')] class extends Component {};?>


<div>
    <livewire:dashboard.dashboard-stats />

    <div class="bg-white rounded-2xl p-5 shadow-sm mt-6">
        <h3 class="font-semibold text-gray-800 mb-4">Recent Projects</h3>
        @php
            $projects = \App\Models\Project::where('user_id', auth()->id())
                ->withCount('tasks')
                ->latest()
                ->take(5)
                ->get();
        @endphp
        <div class="space-y-3">
            @foreach($projects as $p)
                <div class="flex items-center justify-between">
                    <a href="{{ route('projects.kanban', $p) }}" wire:navigate
                       class="text-sm font-medium text-blue-600 hover:underline">{{ $p->name }}</a>
                    <span class="text-xs text-gray-400">{{ $p->tasks_count }} tasks</span>
                </div>
            @endforeach
        </div>
    </div>
</div>



