<?php
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $open = false;

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->latest()->take(10)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->notifications, $this->unreadCount);
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->find($id)?->markAsRead();
        unset($this->notifications, $this->unreadCount);
    }
};?>


{{-- wire:poll.30000ms keeps count fresh --}}
<div wire:poll.30000ms class="relative flex justify-end mb-4">
    {{-- v4: wire:ref="bell" so other components can dispatch to THIS component --}}
    <button wire:ref="bell"
            wire:click="$toggle('open')"
            class="relative p-2 rounded-lg hover:bg-gray-100 transition">
        Bell
        {{-- v4: wire:show for badge visibility --}}
        <span wire:show="unreadCount > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
            {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
        </span>
    </button>

    <div wire:show="open"
         wire:transition.opacity.duration.200ms
         @click.outside="$wire.open = false"
         class="absolute right-0 top-10 w-80 bg-white rounded-xl shadow-xl border z-50">

        <div class="flex items-center justify-between px-4 py-3 border-b">
            <h3 class="font-semibold text-sm">Notifications</h3>
            <button wire:show="unreadCount > 0"
                    wire:click="markAllRead"
                    class="text-xs text-blue-500 hover:underline">
                Mark all read
            </button>
        </div>

        <div class="max-h-80 overflow-y-auto divide-y">
            @forelse($this->notifications as $notification)
                <div wire:click="markRead('{{ $notification->id }}')"
                     class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition {{ $notification->read_at ? 'opacity-50' : '' }}">
                    <p class="text-sm font-medium text-gray-800">{{ $notification->data['title'] ?? 'Notification' }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $notification->data['body'] ?? '' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">All caught up!</div>
            @endforelse
        </div>
    </div>
</div>


