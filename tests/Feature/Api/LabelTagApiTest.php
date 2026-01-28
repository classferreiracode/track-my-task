<?php

use App\Models\TaskLabel;
use App\Models\TaskTag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists labels for the authenticated user', function () {
    $user = User::factory()->create();
    TaskLabel::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/labels')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'color'],
            ],
        ]);
});

it('creates a label', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/labels', [
        'name' => 'Produto',
        'color' => '#123ABC',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Produto');

    $this->assertDatabaseHas('task_labels', [
        'user_id' => $user->id,
        'name' => 'Produto',
        'color' => '#123ABC',
    ]);
});

it('lists tags for the authenticated user', function () {
    $user = User::factory()->create();
    TaskTag::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/tags')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'color'],
            ],
        ]);
});

it('creates a tag', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tags', [
        'name' => 'Sprint',
        'color' => '#0F766E',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Sprint');

    $this->assertDatabaseHas('task_tags', [
        'user_id' => $user->id,
        'name' => 'Sprint',
        'color' => '#0F766E',
    ]);
});
