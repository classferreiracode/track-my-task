<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $durationSeconds = fake()->numberBetween(300, 14400);
        $endedAt = now()->subMinutes(fake()->numberBetween(0, 600));

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'started_at' => $endedAt->subSeconds($durationSeconds),
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ];
    }
}
