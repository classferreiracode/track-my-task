<?php

use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Date;

test('guests are redirected from tasks index', function () {
    $response = $this->get(route('tasks.index'));

    $response->assertRedirect(route('login'));
});

test('users can create tasks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('tasks.store'), [
        'title' => 'Track onboarding',
        'description' => 'Collect time for the kickoff tasks.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Track onboarding',
        'description' => 'Collect time for the kickoff tasks.',
        'is_completed' => false,
    ]);
});

test('users can create task boards', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('tasks.boards.store'), [
        'name' => 'Projeto Alfa',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_boards', [
        'user_id' => $user->id,
        'name' => 'Projeto Alfa',
        'slug' => 'projeto-alfa',
    ]);
});

test('users can create task columns', function () {
    $user = User::factory()->create();
    $board = TaskBoard::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('tasks.columns.store'), [
        'name' => 'Revisão',
        'task_board_id' => $board->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_columns', [
        'user_id' => $user->id,
        'task_board_id' => $board->id,
        'name' => 'Revisão',
        'slug' => 'revisao',
    ]);
});

test('users can start and stop timers', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Date::setTestNow('2026-01-26 09:00:00');
    $this->actingAs($user)
        ->post(route('tasks.timer.start', $task))
        ->assertRedirect();

    Date::setTestNow('2026-01-26 10:15:00');
    $this->actingAs($user)
        ->patch(route('tasks.timer.stop', $task))
        ->assertRedirect();

    $this->assertDatabaseHas('time_entries', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'duration_seconds' => 4500,
    ]);

    Date::setTestNow();
});

test('users cannot start timers for other users tasks', function () {
    $taskOwner = User::factory()->create();
    $task = Task::factory()->for($taskOwner)->create();

    $otherUser = User::factory()->create();
    $this->actingAs($otherUser)
        ->post(route('tasks.timer.start', $task))
        ->assertForbidden();
});

test('users can move tasks between statuses', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $doneColumn = TaskColumn::factory()->for($user)->create([
        'name' => 'Concluídas',
        'slug' => 'done',
        'sort_order' => 3,
    ]);

    Date::setTestNow('2026-01-26 13:00:00');

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'task_column_id' => $doneColumn->id,
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task->task_column_id)->toBe($doneColumn->id)
        ->and($task->is_completed)->toBeTrue()
        ->and($task->completed_at)->not->toBeNull();

    Date::setTestNow();
});

test('users can reorder tasks within a column', function () {
    $user = User::factory()->create();
    $column = TaskColumn::factory()->for($user)->create([
        'name' => 'Backlog',
        'slug' => 'backlog',
        'sort_order' => 1,
    ]);

    $firstTask = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
        'sort_order' => 1,
    ]);
    $secondTask = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
        'sort_order' => 2,
    ]);

    $this->actingAs($user)
        ->patch(route('tasks.order.update'), [
            'column_id' => $column->id,
            'ordered_ids' => [$secondTask->id, $firstTask->id],
        ])
        ->assertRedirect();

    $firstTask->refresh();
    $secondTask->refresh();

    expect($secondTask->sort_order)->toBe(1)
        ->and($firstTask->sort_order)->toBe(2);
});

test('users can reorder columns', function () {
    $user = User::factory()->create();
    $board = TaskBoard::factory()->for($user)->create();
    $firstColumn = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create(['sort_order' => 1]);
    $secondColumn = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create(['sort_order' => 2]);

    $this->actingAs($user)
        ->patch(route('tasks.columns.order'), [
            'task_board_id' => $board->id,
            'ordered_ids' => [$secondColumn->id, $firstColumn->id],
        ])
        ->assertRedirect();

    $firstColumn->refresh();
    $secondColumn->refresh();

    expect($secondColumn->sort_order)->toBe(1)
        ->and($firstColumn->sort_order)->toBe(2);
});

test('users can export a time report as csv', function () {
    $user = User::factory()->create();
    $board = TaskBoard::factory()->for($user)->create();
    $column = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create();
    $task = Task::factory()->for($user)->create([
        'title' => 'Design sprint',
        'task_column_id' => $column->id,
    ]);

    Date::setTestNow('2026-01-26 09:00:00');

    TimeEntry::factory()
        ->for($task)
        ->for($user)
        ->create([
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'duration_seconds' => 3600,
        ]);

    $response = $this->actingAs($user)->get(route('tasks.report', [
        'start' => now()->subDay()->toDateString(),
        'end' => now()->toDateString(),
        'task_board_id' => $board->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain("\xEF\xBB\xBFTask;Status;\"Total (minutes)\";\"Total (hours)\"")
        ->and($content)->toContain('Design sprint');

    Date::setTestNow();
});
