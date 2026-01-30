<?php

namespace Database\Factories;

use App\Models\TaskBoard;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskColumn>
 */
class TaskColumnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'user_id' => User::factory(),
            'task_board_id' => TaskBoard::factory()->state(
                fn (array $attributes) => [
                    'user_id' => $attributes['user_id'],
                    'workspace_id' => Workspace::factory()->state([
                        'owner_user_id' => $attributes['user_id'],
                    ]),
                ],
            ),
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
