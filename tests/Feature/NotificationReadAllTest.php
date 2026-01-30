<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

it('marks all notifications as read', function () {
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

    $notificationA = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => App\Notifications\TaskMentioned::class,
        'data' => ['task_title' => 'Planejar sprint'],
    ]);

    $notificationB = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => App\Notifications\TaskMentioned::class,
        'data' => ['task_title' => 'Revisar cronograma'],
    ]);

    expect($user->unreadNotifications()->count())->toBe(2);

    $response = $this->actingAs($user)->post(route('notifications.read-all'));

    $response->assertRedirect();

    $notificationA = DatabaseNotification::query()->find($notificationA->id);
    $notificationB = DatabaseNotification::query()->find($notificationB->id);

    expect($notificationA?->read_at)->not->toBeNull();
    expect($notificationB?->read_at)->not->toBeNull();
    expect($user->unreadNotifications()->count())->toBe(0);
});
