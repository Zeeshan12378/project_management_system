<?php
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public bool $showModal = false;
    public ?Project $project = null;
    public bool $isEditing = false;

    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:2000')]
    public ?string $description = null;

    #[Validate('required|in:active,archived,completed')]
    public string $status = 'active';

    #[Validate('nullable|date')]
    public ?string $deadline = null;

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->project = $project;
            $this->isEditing = true;
            $this->name = $project->name;
            $this->description = $project->description;
            $this->status = $project->status;
            $this->deadline = $project->deadline?->format('Y-m-d');
        }
    }

    public function openModal(): void { $this->showModal = true; }

    public function save(): void
    {
        $this->validate();
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'deadline' => $this->deadline,
        ];

        if ($this->isEditing) {
            $this->project->update($data);
            $this->dispatch('project-updated');
            $this->dispatch('toast', message: 'Project updated!', type: 'success');
        } else {
            Project::create(array_merge($data, ['user_id' => auth()->id()]));
            $this->dispatch('project-created');
            $this->dispatch('toast', message: 'Project created!', type: 'success');
            $this->reset('name', 'description', 'deadline');
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        $this->project->delete();
        $this->dispatch('project-deleted');
        $this->dispatch('toast', message: 'Project deleted.', type: 'warning');
        $this->showModal = false;
    }
};?>


<div>
    @if($isEditing)
        <button wire:click="openModal" class="text-xs text-blue-500 hover:underline">Edit</button>
    @else
        <button wire:click="openModal"
                class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-blue-700 transition">
            + New Project
        </button>
    @endif

    <div wire:show="showModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         x-on:keydown.escape.window="$wire.showModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4"
             @click.outside="$wire.showModal = false">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-5">
                    {{ $isEditing ? 'Edit Project' : 'New Project' }}
                </h2>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input wire:model.live.debounce.300ms="name" type="text"
                               class="w-full border rounded-xl px-3 py-2 @error('name') border-red-400 @enderror" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="3"
                                  class="w-full border rounded-xl px-3 py-2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select wire:model="status" class="w-full border rounded-xl px-3 py-2">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                            <input wire:model="deadline" type="date"
                                   class="w-full border rounded-xl px-3 py-2" />
                        </div>
                    </div>
                    <div class="flex justify-between pt-4 border-t">
                        @if($isEditing)
                            <button type="button" wire:click="delete"
                                    wire:confirm="Delete this project and all its tasks?"
                                    class="px-4 py-2 text-red-600 border border-red-200 rounded-xl text-sm hover:bg-red-50">
                                Delete
                            </button>
                        @else
                            <div></div>
                        @endif
                        <div class="flex gap-3">
                            <button type="button" wire:click="$set('showModal', false)"
                                    class="px-4 py-2 border rounded-xl text-sm">Cancel</button>
                            <button type="submit"
                                    class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700"
                                    data-loading:class="opacity-60" data-loading:attr="disabled">
                                {{ $isEditing ? 'Update' : 'Create Project' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


