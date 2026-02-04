<?php

use App\Models\TaskBoard;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Mail;

it('allows board creation when below plan limit', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
        'plan' => 'free',
    ]);

    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    TaskBoard::factory()->count(2)->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('tasks.boards.store'), [
        'workspace_id' => $workspace->id,
        'name' => 'Novo board',
    ]);

    $response->assertRedirect();
});

it('blocks invitations when member limit is reached', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
        'plan' => 'free',
    ]);

    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    WorkspaceMembership::factory()->count(2)->create([
        'workspace_id' => $workspace->id,
    ]);

    $response = $this->actingAs($owner)->post(route('workspaces.invitations.store', [
        'workspace' => $workspace->id,
    ]), [
        'email' => 'invitee@example.com',
        'role' => 'member',
    ]);

    $response->assertForbidden();
});
