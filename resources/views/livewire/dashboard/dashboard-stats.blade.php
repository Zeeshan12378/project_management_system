<?php
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

return new #[Lazy] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $uid = auth()->id();
        return [
            'total'     => \App\Models\Task::whereHas('project', fn($q) => $q->where('user_id', $uid))->count(),
            'done_today'=> \App\Models\Task::whereHas('project', fn($q) => $q->where('user_id', $uid))
                              ->where('status', 'done')->whereDate('updated_at', today())->count(),
            'overdue'   => \App\Models\Task::whereHas('project', fn($q) => $q->where('user_id', $uid))
                              ->where('status', '!=', 'done')->where('due_date', '<', today())->count(),
            'in_progress'=> \App\Models\Task::whereHas('project', fn($q) => $q->where('user_id', $uid))
                              ->where('status', 'in_progress')->count(),
        ];
    }
};
?>

<div wire:poll.10000ms>
    @island
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total Tasks',  'key' => 'total',       'color' => 'blue',   'icon' => 'T'],
                ['label' => 'Done Today',   'key' => 'done_today',  'color' => 'green',  'icon' => 'D'],
                ['label' => 'Overdue',      'key' => 'overdue',     'color' => 'red',    'icon' => '!'],
                ['label' => 'In Progress',  'key' => 'in_progress', 'color' => 'yellow', 'icon' => 'P'],
            ] as $stat)
                <div @class([
                    'bg-white rounded-2xl p-5 shadow-sm border-l-4',
                    'border-blue-500'   => $stat['color'] === 'blue',
                    'border-green-500'  => $stat['color'] === 'green',
                    'border-red-500'    => $stat['color'] === 'red',
                    'border-yellow-500' => $stat['color'] === 'yellow',
                ])>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                        <span class="text-xl">{{ $stat['icon'] }}</span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mt-1">
                        {{ $this->stats[$stat['key']] }}
                    </p>
                </div>
            @endforeach
        </div>

        @placeholder
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-pulse">
                @foreach(range(1,4) as $i)
                    <div class="bg-gray-200 rounded-2xl h-24"></div>
                @endforeach
            </div>
        @endisland
    @endisland
</div>



