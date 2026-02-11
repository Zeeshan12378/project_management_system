<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::livewire('auth', 'auth-page')->name('auth');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('auth');
})->name('logout');

// Route::get('/dashboard', function () {
//     return "Welcome User!";
// })->middleware('auth')->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::livewire('/dashboard', 'dashboard.dashboard')->name('dashboard');
    Route::livewire('/projects', 'projects.project-list')->name('projects.index');
    Route::livewire('/projects/{project}/tasks', 'tasks.task-list')->name('projects.tasks');
    Route::livewire('/projects/{project}/kanban', 'tasks.kanban-board')->name('projects.kanban');
});
