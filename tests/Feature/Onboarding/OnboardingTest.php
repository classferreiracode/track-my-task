<?php

use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\User;
use App\Models\Workspace;

test('users without workspaces are redirected to onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});

test('onboarding creates workspace board and default columns', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'), [
            'workspace_name' => 'Empresa Alpha',
            'board_name' => 'Projeto Alfa',
        ])
        ->assertRedirect();

    $workspaceId = Workspace::query()
        ->where('owner_user_id', $user->id)
        ->where('name', 'Empresa Alpha')
        ->value('id');

    $this->assertNotNull($workspaceId);

    $boardId = TaskBoard::query()
        ->where('workspace_id', $workspaceId)
        ->where('name', 'Projeto Alfa')
        ->value('id');

    $this->assertNotNull($boardId);

    $this->assertDatabaseHas('task_columns', [
        'task_board_id' => $boardId,
        'slug' => 'backlog',
    ]);

    $this->assertDatabaseHas('task_columns', [
        'task_board_id' => $boardId,
        'slug' => 'em-progresso',
    ]);

    $this->assertDatabaseHas('task_columns', [
        'task_board_id' => $boardId,
        'slug' => 'concluido',
    ]);

    expect(TaskColumn::query()->where('task_board_id', $boardId)->count())->toBe(3);
});
