<?php
use App\Models\Comment;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public int $taskId;

    #[Validate('required|string|min:1|max:2000')]
    public string $body = '';

    public function mount(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function addComment(): void
    {
        $this->validate();
        Comment::create([
            'task_id' => $this->taskId,
            'user_id' => auth()->id(),
            'body'    => $this->body,
        ]);
        $this->body = '';
        $this->dispatch('toast', message: 'Comment added!', type: 'success');
    }

    public function deleteComment(Comment $comment): void
    {
        $this->authorize('delete', $comment);
        $comment->delete();
    }

    public function with(): array
    {
        return [
            'comments' => Comment::where('task_id', $this->taskId)
                ->with('user')
                ->latest()
                ->get(),
        ];
    }
};?>


{{-- v4: $slot works natively in Livewire components --}}
<div class="space-y-3">
    {{-- $slot: callers can inject extra UI above comments --}}
    {{ $slot ?? '' }}

    @foreach($comments as $comment)
        <div class="flex gap-3">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
            </div>
            <div class="flex-1 bg-gray-50 rounded-xl px-4 py-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-800">{{ $comment->user->name }}</span>
                    <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm text-gray-700">{{ $comment->body }}</p>
            </div>
            @can('delete', $comment)
                <button wire:click="deleteComment({{ $comment->id }})"
                        wire:confirm="Delete this comment?"
                        class="text-gray-300 hover:text-red-400 self-start mt-2 text-sm">x</button>
            @endcan
        </div>
    @endforeach

    {{-- New comment form --}}
    <form wire:submit="addComment" class="flex gap-3 mt-4">
        <div class="flex-1">
            <textarea wire:model="body"
                      rows="2"
                      placeholder="Write a comment..."
                      class="w-full border rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 @error('body') border-red-400 @enderror resize-none"></textarea>
            @error('body')
                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit"
                class="self-end px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition"
                data-loading:class="opacity-50" data-loading:attr="disabled">
            Send
        </button>
    </form>
</div>


