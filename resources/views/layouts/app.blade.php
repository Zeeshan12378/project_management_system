<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Project Manager' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50" x-data>
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">
            <div class="p-5 border-b border-gray-700">
                <h1 class="text-lg font-bold">ProjectHub</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ auth()->user()->name }}</p>
            </div>

            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('dashboard') }}" wire:navigate
                   wire:current="active" wire:current:class="bg-gray-700 text-white"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition text-sm">
                    Dashboard
                </a>
                <a href="{{ route('projects.index') }}" wire:navigate
                   wire:current="active" wire:current:class="bg-gray-700 text-white"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition text-sm">
                    Projects
                </a>
            </nav>

            <div class="p-4 border-t border-gray-700">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b px-6 py-3 flex items-center justify-between flex-shrink-0">
                <h2 class="text-sm font-medium text-gray-500">
                    {{ $header ?? '' }}
                </h2>
                <livewire:notifications.notification-bell />
            </header>

            <div class="flex-1 overflow-auto p-6">
                {{ $slot }}
            </div>
        </main>
    </div>

    <div id="toasts" class="fixed bottom-4 right-4 z-50 space-y-2 pointer-events-none"></div>

    @livewireScripts

    <script>
        window.addEventListener('toast', e => {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500',
            };
            const el = document.createElement('div');
            el.className = `pointer-events-auto ${colors[e.detail.type ?? 'success']} text-white px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 min-w-[240px] text-sm transition-all`;
            el.innerHTML = `<span class="flex-1">${e.detail.message}</span><button onclick="this.closest('div').remove()" class="opacity-70 hover:opacity-100">x</button>`;
            document.getElementById('toasts').appendChild(el);
            setTimeout(() => el.style.opacity = '0', 3600);
            setTimeout(() => el.remove(), 4000);
        });
    </script>
</body>
</html>
