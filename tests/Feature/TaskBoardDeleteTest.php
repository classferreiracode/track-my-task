<?php

use App\Models\TaskBoard;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Str;

it('allows the board creator to delete their board', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $creator = User::factory()->create();
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $creator->id,
        'role' => 'member',
    ]);

    $boardName = 'Board de Teste';
    $board = TaskBoard::query()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $creator->id,
        'name' => $boardName,
        'slug' => Str::slug($boardName),
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($creator)->delete(
        route('tasks.boards.destroy', $board),
    );

    $response->assertRedirect();

    $this->assertDatabaseMissing('task_boards', [
        'id' => $board->id,
    ]);
});

it('allows owners to delete boards created by others', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $owner->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $creator = User::factory()->create();
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $creator->id,
        'role' => 'member',
    ]);

    $boardName = 'Board de Teste';
    $board = TaskBoard::query()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $creator->id,
        'name' => $boardName,
        'slug' => Str::slug($boardName),
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($owner)->delete(
        route('tasks.boards.destroy', $board),
    );

    $response->assertRedirect();

    $this->assertDatabaseMissing('task_boards', [
        'id' => $board->id,
    ]);
});
