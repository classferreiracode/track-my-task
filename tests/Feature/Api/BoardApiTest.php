<?php

use App\Models\TaskBoard;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication for board listing', function () {
    $this->getJson('/api/v1/boards')->assertUnauthorized();
});

it('lists boards for the authenticated user', function () {
    $user = User::factory()->create();
    TaskBoard::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/boards')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'slug', 'sort_order'],
            ],
        ]);
});

it('creates a board', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/boards', [
        'name' => 'API Board',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'API Board');

    $this->assertDatabaseHas('task_boards', [
        'user_id' => $user->id,
        'name' => 'API Board',
        'slug' => 'api-board',
    ]);
});
