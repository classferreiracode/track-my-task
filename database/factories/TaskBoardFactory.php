<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskBoard>
 */
class TaskBoardFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function ($board) {
            if ($board->workspace_id && $board->workspace?->owner_user_id === $board->user_id) {
                return;
            }

            $workspace = Workspace::factory()->create([
                'owner_user_id' => $board->user_id,
            ]);

            $board->update([
                'workspace_id' => $workspace->id,
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $owner = User::factory();
        $workspace = Workspace::factory()->state([
            'owner_user_id' => $owner,
        ]);

        return [
            'user_id' => $owner,
            'workspace_id' => $workspace,
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
