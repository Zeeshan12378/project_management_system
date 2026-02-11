<?php
use App\Forms\TaskForm as TaskFormObject;
use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public TaskFormObject $form;
    public bool $showModal = false;
    public ?Task $task     = null;
    public bool $isEditing = false;
    public int $projectId = 0;

    public function mount(?Task $task = null, int $projectId = 0): void
    {
        $this->projectId = $projectId;
        if ($task?->exists) {
            $this->task      = $task;
            $this->isEditing = true;
            $this->form->fill($task);
        }
    }

    #[On('open-task-form')]
    public function openModal(?int $projectId = null, string $status = 'todo'): void
    {
        if (!$this->isEditing) {
            $this->form->reset();
            $this->form->project_id = $projectId ?? $this->projectId;
            $this->form->status = $status;
        }
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->validate();

        if ($this->isEditing) {
            Gate::authorize('update', $this->task);
            $this->task->update($this->form->toArray());
            $this->dispatch('task-updated', taskId: $this->task->id);
        } else {
            $task = Task::create($this->form->toArray());
            $this->dispatch('task-created', taskId: $task->id);
            $this->form->reset();
        }

        $this->dispatch('toast', message: $this->isEditing ? 'Task updated!' : 'Task created!', type: 'success');
        $this->showModal = false;
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->task);
        $this->task->delete();
        $this->dispatch('task-deleted');
        $this->dispatch('toast', message: 'Task deleted.', type: 'warning');
        $this->showModal = false;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->form->resetValidation();
    }

    public function with(): array
    {
        return ['users' => \App\Models\User::select('id', 'name')->get()];
    }
};?>


<div>
    {{-- Trigger --}}
    @if($isEditing)
        <button wire:click="openModal" class="text-sm text-blue-500 hover:underline">Edit</button>
    @else
        <button wire:click="$dispatch('open-task-form', { projectId: {{ $projectId }} })"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            + New Task
        </button>
    @endif

    {{-- v4: wire:show instead of @if for modal (CSS toggle, no DOM removal) --}}
    <div wire:show="showModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         x-on:keydown.escape.window="$wire.closeModal()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto"
             @click.outside="$wire.closeModal()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ $isEditing ? 'Edit Task' : 'Create New Task' }}
                    </h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">x</button>
                </div>

                <form wire:submit="save" class="space-y-5">
                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input wire:model.live.debounce.300ms="form.title"
                               type="text"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('form.title') border-red-400 @enderror"
                               placeholder="What needs to be done?" />
                        @error('form.title')
                            <p class="mt-1 text-sm text-red-500 flex items-center gap-1">! {{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="form.description" rows="3"
                                  class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                  placeholder="Add details..."></textarea>
                    </div>

                    {{-- Status & Priority --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select wire:model="form.status" class="w-full border rounded-lg px-3 py-2">
                                <option value="todo">Todo</option>
                                <option value="in_progress">In Progress</option>
                                <option value="review">Review</option>
                                <option value="done">Done</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select wire:model="form.priority" class="w-full border rounded-lg px-3 py-2">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    {{-- Assignee & Due Date --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                            <select wire:model="form.assigned_to" class="w-full border rounded-lg px-3 py-2">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                            <input wire:model="form.due_date" type="date"
                                   class="w-full border rounded-lg px-3 py-2 @error('form.due_date') border-red-400 @enderror" />
                            @error('form.due_date')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-4 border-t">
                        @if($isEditing)
                            <button type="button"
                                    wire:click="delete"
                                    wire:confirm="Delete this task permanently?"
                                    class="px-4 py-2 text-red-600 border border-red-200 rounded-lg hover:bg-red-50 text-sm">
                                Delete Task
                            </button>
                        @else
                            <div></div>
                        @endif
                        <div class="flex gap-3">
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                            {{-- v4: data-loading:class — no wire:loading needed --}}
                            <button type="submit"
                                    class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition"
                                    data-loading:class="opacity-75 cursor-not-allowed"
                                    data-loading:attr="disabled">
                                {{ $isEditing ? 'Update Task' : 'Create Task' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


