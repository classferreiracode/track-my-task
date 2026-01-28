<?php

namespace Database\Factories;

use App\Models\TaskColumn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'task_column_id' => TaskColumn::factory()->state(
                fn (array $attributes) => ['user_id' => $attributes['user_id']],
            ),
            'starts_at' => fake()->optional()->date(),
            'ends_at' => fake()->optional()->date(),
            'sort_order' => fake()->numberBetween(1, 20),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => 'normal',
            'is_completed' => false,
            'completed_at' => null,
        ];
    }
}
