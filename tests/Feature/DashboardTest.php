<?php

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('kpi', fn (Assert $kpi) => $kpi
            ->where('total_tasks', 0)
            ->where('completed_tasks', 0)
            ->where('active_timers', 0)
            ->where('active_task_names', [])
            ->where('seconds_today', 0)
            ->where('seconds_week', 0)
            ->where('seconds_month', 0)
            ->has('day_start')
            ->has('week_start')
            ->has('month_start')
            ->has('as_of')
        )
    );
});

test('dashboard includes active task names', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'title' => 'Urgent task',
    ]);

    TimeEntry::factory()->for($task)->for($user)->create([
        'ended_at' => null,
        'duration_seconds' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('kpi.active_timers', 1)
            ->where('kpi.active_task_names', ['Urgent task'])
        );
});
