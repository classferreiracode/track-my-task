<?php

use App\Models\TaskBoard;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Laravel\Sanctum\Sanctum;

it('requires authentication for board listing', function () {
    $this->getJson('/api/v1/boards')->assertUnauthorized();
});

it('lists boards for the authenticated user', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/boards?workspace_id='.$workspace->id)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'slug', 'sort_order', 'workspace_id'],
            ],
        ]);
});

it('creates a board', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/boards', [
        'name' => 'API Board',
        'workspace_id' => $workspace->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'API Board');

    $this->assertDatabaseHas('task_boards', [
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'name' => 'API Board',
        'slug' => 'api-board',
    ]);
});
