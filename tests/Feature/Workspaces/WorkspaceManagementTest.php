<?php

use App\Mail\WorkspaceInvitationMail;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Mail;

test('users can create up to two workspaces on free plan', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
        'slug' => 'workspace-0',
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Workspace 1',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Workspace 2',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Workspace 3',
    ])->assertSessionHasErrors('name');
});

test('workspace owners can invite and members can accept invitations', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $invitee = User::factory()->create([
        'email' => 'invitee@example.com',
    ]);
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => $invitee->email,
            'role' => 'member',
        ])
        ->assertRedirect();

    $invitation = WorkspaceInvitation::query()->where('email', $invitee->email)->first();

    expect($invitation)->not->toBeNull();
    Mail::assertQueued(WorkspaceInvitationMail::class, function (WorkspaceInvitationMail $mail) use ($invitee) {
        return $mail->hasTo($invitee->email);
    });

    $this->actingAs($invitee)
        ->get(route('workspaces.invitations.accept', $invitation->token))
        ->assertRedirect();

    $this->assertDatabaseHas('workspace_memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $invitee->id,
        'role' => 'member',
    ]);
});

test('invitation landing page stores session data for guests', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by_user_id' => $owner->id,
        'email' => 'guest@example.com',
        'role' => 'member',
    ]);

    $this->get(route('workspaces.invitations.show', $invitation->token))
        ->assertOk()
        ->assertSessionHas('invitation_token', $invitation->token)
        ->assertSessionHas('url.intended', route('tasks.index', [
            'workspace' => $workspace->id,
        ]));
});

test('tasks can be assigned to workspace members only', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => 'member',
    ]);
    $board = TaskBoard::factory()->create([
        'user_id' => $owner->id,
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()->for($board, 'board')->create([
        'user_id' => $owner->id,
    ]);

    $this->actingAs($owner)->post(route('tasks.store'), [
        'title' => 'Delegated task',
        'task_column_id' => $column->id,
        'assignees' => [$member->id, $outsider->id],
    ])->assertRedirect();

    $taskId = Task::query()
        ->where('task_column_id', $column->id)
        ->where('title', 'Delegated task')
        ->value('id');

    $this->assertDatabaseHas('task_user', [
        'task_id' => $taskId,
        'user_id' => $member->id,
    ]);

    $this->assertDatabaseMissing('task_user', [
        'task_id' => $taskId,
        'user_id' => $outsider->id,
    ]);
});
