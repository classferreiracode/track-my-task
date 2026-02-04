<?php

use App\Models\ManagementUser;
use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Support\Facades\Hash;

it('updates plan pricing and limits from management panel', function () {
    $manager = ManagementUser::query()->create([
        'name' => 'Manager',
        'email' => 'manager-plans@example.com',
        'password' => Hash::make('secret123'),
        'role' => 'owner',
        'is_active' => true,
    ]);

    $plan = Plan::query()->firstOrCreate(
        ['key' => 'free'],
        ['name' => 'Free', 'description' => 'Starter plan for small teams.', 'price_monthly' => 0],
    );

    PlanLimit::query()->updateOrCreate(
        ['plan_id' => $plan->id, 'limit_key' => 'max_members'],
        ['limit_value' => 3],
    );

    $payload = [
        'price_monthly' => 49,
        'limits' => [
            'max_members' => 5,
            'max_boards' => 10,
            'max_tasks_per_board' => null,
            'max_exports_per_month' => 2,
            'max_active_timers_per_user' => 1,
        ],
    ];

    $this->actingAs($manager, 'management')
        ->patch(route('management.plans.update', $plan), $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('plans', [
        'id' => $plan->id,
        'price_monthly' => 49,
    ]);

    $this->assertDatabaseHas('plan_limits', [
        'plan_id' => $plan->id,
        'limit_key' => 'max_members',
        'limit_value' => 5,
    ]);
});
