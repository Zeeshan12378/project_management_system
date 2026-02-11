<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'example@example.com'],
            [
                'name'     => 'Test User',
                'password' => Hash::make('Aa12345@')
            ]
        );

        // Create 3 projects, each with 10 tasks
        \App\Models\Project::factory(3)
            ->for($user)
            ->has(\App\Models\Task::factory(10))
            ->create();
    }
}
