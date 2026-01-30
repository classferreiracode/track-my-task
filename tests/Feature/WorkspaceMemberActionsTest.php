<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Notifications\WorkspaceMemberChanged;
use Illuminate\Support\Facades\Notification;

it('allows owners to remove members and notifies them', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $member = User::factory()->create();
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => 'member',
    ]);

    $response = $this->actingAs($owner)->delete(
        route('workspaces.members.destroy', [
            'workspace' => $workspace,
            'user' => $member,
        ]),
    );

    $response->assertRedirect();

    $this->assertDatabaseMissing('workspace_memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);

    Notification::assertSentTo(
        $member,
        WorkspaceMemberChanged::class,
        fn (WorkspaceMemberChanged $notification) => $notification->type === 'removed'
            && $notification->workspace->is($workspace),
    );
});

it('allows members to leave a workspace and notifies them', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $member = User::factory()->create();
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => 'member',
    ]);

    $response = $this->actingAs($member)->delete(
        route('workspaces.members.leave', [
            'workspace' => $workspace,
        ]),
    );

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('workspace_memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);

    Notification::assertSentTo(
        $member,
        WorkspaceMemberChanged::class,
        fn (WorkspaceMemberChanged $notification) => $notification->type === 'left'
            && $notification->workspace->is($workspace),
    );
});

it('allows admins to update owner capacity without changing role', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $admin = User::factory()->create();
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $admin->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->patch(
        route('workspaces.members.update', [
            'workspace' => $workspace,
            'user' => $owner,
        ]),
        [
            'role' => 'owner',
            'weekly_capacity_minutes' => 1200,
        ],
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('workspace_memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'weekly_capacity_minutes' => 1200,
    ]);
});
