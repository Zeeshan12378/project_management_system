<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ProjectHub' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">ProjectHub</h1>
            <p class="text-gray-500 mt-1 text-sm">Manage your projects and team</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>
</html>
