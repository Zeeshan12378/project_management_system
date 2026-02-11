<?php
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Task $task;

    #[Validate([
        'files.*' => 'file|max:10240|mimes:pdf,png,jpg,jpeg,gif,doc,docx,xlsx,zip'
    ])]
    public array $files = [];

    public function updatedFiles(): void
    {
        $this->validateOnly('files.*');
    }

    public function upload(): void
    {
        $this->validate();
        foreach ($this->files as $file) {
            $path = $file->store("attachments/tasks/{$this->task->id}", 'public');
            $this->task->attachments()->create([
                'user_id'   => auth()->id(),
                'filename'  => $file->getClientOriginalName(),
                'path'      => $path,
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
            ]);
        }
        $this->files = [];
        $this->task->refresh();
        $this->dispatch('toast', message: 'Files uploaded!', type: 'success');
    }

    public function removeFile(int $id): void
    {
        $f = Attachment::findOrFail($id);
        Storage::disk('public')->delete($f->path);
        $f->delete();
        $this->task->refresh();
    }
};?>


<div class="space-y-4">
    {{-- Drop Zone --}}
    <div x-data="{ drag: false }"
         x-on:dragover.prevent="drag = true"
         x-on:dragleave.prevent="drag = false"
         x-on:drop.prevent="
             drag = false;
             $refs.input.files = $event.dataTransfer.files;
             $wire.upload('files', $event.dataTransfer.files)"
         :class="drag ? 'border-blue-400 bg-blue-50' : 'border-gray-300 bg-gray-50'"
         class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
         wire:click="$refs.input.click()">

        <input x-ref="input" wire:model="files" type="file" multiple class="hidden" />
        <p class="text-3xl mb-2">Upload</p>
        <p class="text-sm text-gray-600 font-medium">Drop files here or <span class="text-blue-500">browse</span></p>
        <p class="text-xs text-gray-400 mt-1">PDF, Images, Docs - Max 10MB each</p>
    </div>

    {{-- v4: data-loading states (no wire:loading div needed) --}}
    <div data-loading:class="flex"
         data-loading:class.remove="hidden"
         class="hidden items-center gap-2 text-sm text-blue-600">
        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Uploading files...
    </div>

    {{-- Staged previews --}}
    @if(count($files))
        <div class="space-y-2">
            @foreach($files as $i => $file)
                <div class="flex items-center gap-3 p-2 bg-white border rounded-lg">
                    @if(str_contains($file->getMimeType(), 'image'))
                        <img src="{{ $file->temporaryUrl() }}" class="w-10 h-10 object-cover rounded">
                    @else
                        <div class="w-10 h-10 bg-blue-50 rounded flex items-center justify-center text-xs font-bold text-blue-600">
                            {{ strtoupper($file->getClientOriginalExtension()) }}
                        </div>
                    @endif
                    <span class="text-sm flex-1 truncate">{{ $file->getClientOriginalName() }}</span>
                    <span class="text-xs text-gray-400">{{ number_format($file->getSize()/1024, 1) }} KB</span>
                </div>
            @endforeach
            <button wire:click="upload"
                    class="w-full py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
                    data-loading:attr="disabled" data-loading:class="opacity-60">
                Upload {{ count($files) }} file(s)
            </button>
        </div>
    @endif

    {{-- Stored Attachments --}}
    @foreach($task->attachments as $a)
        <div class="flex items-center gap-3 p-3 bg-white border rounded-lg">
            <a href="{{ Storage::url($a->path) }}" target="_blank"
               class="flex-1 text-sm text-blue-600 hover:underline truncate">
                File: {{ $a->filename }}
            </a>
            <span class="text-xs text-gray-400">{{ number_format($a->size/1024, 1) }} KB</span>
            <button wire:click="removeFile({{ $a->id }})" wire:confirm="Remove this file?"
                    class="text-red-400 hover:text-red-600 text-xs">x</button>
        </div>
    @endforeach
</div>


