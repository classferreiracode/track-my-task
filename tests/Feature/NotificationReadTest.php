<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

it('marks notifications as read', function () {
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

    $notification = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => App\Notifications\TaskMentioned::class,
        'data' => [
            'task_title' => 'Ajustar relatório',
            'actor' => ['name' => 'Ana'],
        ],
    ]);

    expect($user->unreadNotifications()->count())->toBe(1);

    $response = $this->actingAs($user)->post(
        route('notifications.read', $notification->id),
    );

    $response->assertRedirect();

    $notification = DatabaseNotification::query()->find($notification->id);

    expect($notification)->not->toBeNull();
    expect($notification->read_at)->not->toBeNull();
    expect($user->unreadNotifications()->count())->toBe(0);
});
