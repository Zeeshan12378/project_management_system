<?php
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $taskId = null;
    public bool $showPanel = false;

    #[On('open-task-detail')]
    public function openPanel(int $taskId): void
    {
        $this->taskId    = $taskId;
        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->taskId    = null;
    }
};?>


{{-- v4: wire:show for CSS display toggle --}}
<div wire:show="showPanel"
     class="fixed inset-y-0 right-0 w-[600px] bg-white shadow-2xl z-40 flex flex-col"
     wire:transition.opacity.duration.300ms>

    <div class="flex items-center justify-between p-6 border-b">
        <h2 class="text-lg font-semibold">Task Details</h2>
        <button wire:click="closePanel" class="text-gray-400 hover:text-gray-600 text-xl">x</button>
    </div>

    @if($taskId)
        {{-- Main task info renders normally --}}
        <div class="p-6 space-y-4 flex-1 overflow-auto">
            {{--
                @island isolates this expensive comments section.
                It loads separately and won't block the panel from opening.
                Uses lazy: true so it loads only after the panel is visible.
            --}}
            @island(lazy: true)
                @php
                    $task = \App\Models\Task::with(['comments.user', 'attachments', 'assignee'])->find($this->taskId);
                @endphp
                @if($task)
                    <div>
                        <h3 class="font-semibold text-gray-900 text-xl mb-2">{{ $task->title }}</h3>
                        <p class="text-gray-600">{{ $task->description ?? 'No description.' }}</p>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500">Status</span>
                                <p class="font-medium mt-0.5">{{ $task->status_label }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500">Priority</span>
                                <p class="font-medium mt-0.5">{{ ucfirst($task->priority) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500">Assignee</span>
                                <p class="font-medium mt-0.5">{{ $task->assignee?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500">Due Date</span>
                                <p class="font-medium mt-0.5">{{ $task->due_date?->format('M d, Y') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Attachments - also in island --}}
                    <div class="mt-6">
                        <h4 class="font-medium mb-2">Attachments ({{ $task->attachments->count() }})</h4>
                        @foreach($task->attachments as $attachment)
                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded mb-1">
                                File:
                                <a href="{{ Storage::url($attachment->path) }}" target="_blank"
                                   class="text-sm text-blue-600 hover:underline">
                                    {{ $attachment->filename }}
                                </a>
                                <span class="text-xs text-gray-400 ml-auto">
                                    {{ number_format($attachment->size / 1024, 1) }} KB
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Comments --}}
                    <div class="mt-6">
                        <h4 class="font-medium mb-3">Comments ({{ $task->comments->count() }})</h4>
                        <livewire:comments.comment-section :task-id="$task->id" />
                    </div>
                @endif

                @placeholder
                    {{-- Skeleton shown while island loads --}}
                    <div class="animate-pulse space-y-4">
                        <div class="h-6 bg-gray-200 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-200 rounded w-full"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                        <div class="grid grid-cols-2 gap-3 mt-4">
                            @foreach(range(1,4) as $i)
                                <div class="bg-gray-200 rounded-lg h-16"></div>
                            @endforeach
                        </div>
                    </div>
                @endisland
            @endisland
        </div>
    @endif
</div>


