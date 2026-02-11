<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'name'        => fake()->bs(),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(['active', 'archived', 'completed']),
            'deadline'    => fake()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
