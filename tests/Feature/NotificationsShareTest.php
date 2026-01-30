<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

it('shares notifications in inertia props', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);

    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => App\Notifications\TaskMentioned::class,
        'data' => [
            'task_title' => 'Planejar campanha',
            'actor' => ['name' => 'Ana'],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('notifications')
        ->has('notifications.items', 1)
        ->where('notifications.unread_count', 1)
        ->where('notifications.items.0.data.task_title', 'Planejar campanha')
    );
});
