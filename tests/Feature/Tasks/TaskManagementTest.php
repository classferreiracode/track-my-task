<?php

use App\Mail\TaskStatusMail;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\TaskLabel;
use App\Models\TaskTag;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

function createWorkspaceForUser(User $user): Workspace
{
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);

    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    return $workspace;
}

function createBoardForUser(User $user, Workspace $workspace): TaskBoard
{
    return TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
}

function createColumnForUser(User $user, TaskBoard $board, array $attributes = []): TaskColumn
{
    return TaskColumn::factory()->for($user)->for($board, 'board')->create($attributes);
}

test('guests are redirected from tasks index', function () {
    $response = $this->get(route('tasks.index'));

    $response->assertRedirect(route('login'));
});

test('users can create tasks', function () {
    Mail::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()->for($board, 'board')->create([
        'user_id' => $user->id,
    ]);
    $label = TaskLabel::factory()->for($user)->create([
        'name' => 'Urgente',
        'color' => '#FF0000',
    ]);
    $tag = TaskTag::factory()->for($user)->create([
        'name' => 'Cliente A',
        'color' => '#00FF00',
    ]);
    $assignee = User::factory()->create();
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $assignee->id,
        'role' => 'member',
    ]);

    $this->actingAs($user);

    $response = $this->post(route('tasks.store'), [
        'title' => 'Track onboarding',
        'description' => 'Collect time for the kickoff tasks.',
        'priority' => 'alta',
        'starts_at' => '2026-02-01',
        'ends_at' => '2026-02-05',
        'task_column_id' => $column->id,
        'labels' => [$label->id],
        'tags' => [$tag->id],
        'assignees' => [$assignee->id],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Track onboarding',
        'description' => 'Collect time for the kickoff tasks.',
        'priority' => 'alta',
        'starts_at' => '2026-02-01',
        'ends_at' => '2026-02-05',
        'is_completed' => false,
    ]);

    $taskId = Task::query()
        ->where('user_id', $user->id)
        ->where('title', 'Track onboarding')
        ->value('id');

    $this->assertDatabaseHas('task_label_task', [
        'task_id' => $taskId,
        'task_label_id' => $label->id,
    ]);

    $this->assertDatabaseHas('task_tag_task', [
        'task_id' => $taskId,
        'task_tag_id' => $tag->id,
    ]);

    Mail::assertQueued(TaskStatusMail::class, function (TaskStatusMail $mail) use ($assignee) {
        return $mail->type === 'assigned'
            && $mail->hasTo($assignee->email);
    });
});

test('users can create task boards', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)->post(route('tasks.boards.store'), [
        'name' => 'Projeto Alfa',
        'workspace_id' => $workspace->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_boards', [
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'name' => 'Projeto Alfa',
        'slug' => 'projeto-alfa',
    ]);
});

test('users can create task columns', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);

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

test('users can create task labels', function () {
    $user = User::factory()->create();
    createWorkspaceForUser($user);

    $response = $this->actingAs($user)->post(route('tasks.labels.store'), [
        'name' => 'Financeiro',
        'color' => '#1D4ED8',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_labels', [
        'user_id' => $user->id,
        'name' => 'Financeiro',
        'color' => '#1D4ED8',
    ]);
});

test('users can update task label colors', function () {
    $user = User::factory()->create();
    createWorkspaceForUser($user);
    $label = TaskLabel::factory()->for($user)->create([
        'color' => '#1D4ED8',
    ]);

    $response = $this->actingAs($user)->patch(route('tasks.labels.update', $label), [
        'color' => '#FF5733',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_labels', [
        'id' => $label->id,
        'user_id' => $user->id,
        'color' => '#FF5733',
    ]);
});

test('users can create task tags', function () {
    $user = User::factory()->create();
    createWorkspaceForUser($user);

    $response = $this->actingAs($user)->post(route('tasks.tags.store'), [
        'name' => 'Operacao',
        'color' => '#0F766E',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_tags', [
        'user_id' => $user->id,
        'name' => 'Operacao',
        'color' => '#0F766E',
    ]);
});

test('users can update task tag colors', function () {
    $user = User::factory()->create();
    createWorkspaceForUser($user);
    $tag = TaskTag::factory()->for($user)->create([
        'color' => '#0F766E',
    ]);

    $response = $this->actingAs($user)->patch(route('tasks.tags.update', $tag), [
        'color' => '#14B8A6',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_tags', [
        'id' => $tag->id,
        'user_id' => $user->id,
        'color' => '#14B8A6',
    ]);
});

test('users can start and stop timers', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceForUser($user);
    $board = createBoardForUser($user, $workspace);
    $column = createColumnForUser($user, $board);
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
    ]);

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
    $workspace = createWorkspaceForUser($taskOwner);
    $board = createBoardForUser($taskOwner, $workspace);
    $column = createColumnForUser($taskOwner, $board);
    $task = Task::factory()->for($taskOwner)->create([
        'task_column_id' => $column->id,
    ]);

    $otherUser = User::factory()->create();
    createWorkspaceForUser($otherUser);
    $this->actingAs($otherUser)
        ->post(route('tasks.timer.start', $task))
        ->assertForbidden();
});

test('users can move tasks between statuses', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceForUser($user);
    $board = createBoardForUser($user, $workspace);
    $backlog = createColumnForUser($user, $board, [
        'name' => 'Backlog',
        'slug' => 'backlog',
        'sort_order' => 1,
    ]);
    $doneColumn = createColumnForUser($user, $board, [
        'name' => 'Concluídas',
        'slug' => 'done',
        'sort_order' => 3,
    ]);
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $backlog->id,
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
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create([
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

test('moving tasks to the done column marks them as completed', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $backlog = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create([
            'name' => 'Backlog',
            'slug' => 'backlog',
        ]);
    $done = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create([
            'name' => 'Concluídas',
            'slug' => 'done',
        ]);
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $backlog->id,
        'is_completed' => false,
        'completed_at' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('tasks.order.update'), [
            'column_id' => $done->id,
            'ordered_ids' => [$task->id],
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task->task_column_id)->toBe($done->id)
        ->and($task->is_completed)->toBeTrue()
        ->and($task->completed_at)->not->toBeNull();
});

test('moving a running task to done stops the timer', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $done = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create([
            'slug' => 'done',
        ]);
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $done->id,
    ]);

    Date::setTestNow('2026-01-27 09:00:00');

    $entry = TimeEntry::factory()
        ->for($task)
        ->for($user)
        ->create([
            'started_at' => now()->subMinutes(45),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

    Date::setTestNow('2026-01-27 10:00:00');

    $this->actingAs($user)
        ->patch(route('tasks.order.update'), [
            'column_id' => $done->id,
            'ordered_ids' => [$task->id],
        ])
        ->assertRedirect();

    $entry->refresh();

    expect($entry->ended_at)->not->toBeNull()
        ->and($entry->duration_seconds)->toBe(6300);

    Date::setTestNow();
});

test('users can reorder columns', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
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
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()
        ->for($user)
        ->for($board, 'board')
        ->create();
    $task = Task::factory()->for($user)->create([
        'title' => 'Design sprint',
        'task_column_id' => $column->id,
    ]);

    Date::setTestNow('2026-01-27 10:00:00');

    TimeEntry::factory()->for($task)->for($user)->create([
        'started_at' => Date::parse('2026-01-27 08:30:00'),
        'ended_at' => Date::parse('2026-01-27 09:45:00'),
        'duration_seconds' => 4500,
    ]);

    $response = $this->actingAs($user)->get(route('tasks.report', [
        'start' => '2026-01-27',
        'end' => '2026-01-27',
        'task_board_id' => $board->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain("\xEF\xBB\xBFTask;Status;Day;Play;Pause;\"Total (minutes)\";\"Total (hours)\"")
        ->and($content)->toContain('Design sprint')
        ->and($content)->toContain('27/01/2026;08:30:00;09:45:00;75;1.25');

    Date::setTestNow();
});

test('sending a task to another status dispatches an email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $fromColumn = TaskColumn::factory()->for($user)->for($board, 'board')->create([
        'name' => 'Backlog',
        'slug' => 'backlog',
    ]);
    $toColumn = TaskColumn::factory()->for($user)->for($board, 'board')->create([
        'name' => 'Em progresso',
        'slug' => 'em-progresso',
    ]);
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $fromColumn->id,
    ]);

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'task_column_id' => $toColumn->id,
        ])
        ->assertRedirect();

    Mail::assertQueued(TaskStatusMail::class, function (TaskStatusMail $mail) use ($user, $toColumn) {
        return $mail->type === 'status_changed'
            && $mail->payload['status'] === $toColumn->name
            && $mail->hasTo($user->email);
    });
});

test('completing a task dispatches an email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()->for($user)->for($board, 'board')->create();
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
        'is_completed' => false,
        'completed_at' => null,
    ]);

    Date::setTestNow('2026-02-02 09:00:00');

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'is_completed' => true,
        ])
        ->assertRedirect();

    Mail::assertQueued(TaskStatusMail::class, function (TaskStatusMail $mail) use ($user) {
        return $mail->type === 'completed'
            && $mail->hasTo($user->email);
    });

    Date::setTestNow();
});

test('overdue tasks dispatch an email once', function () {
    Mail::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()->for($user)->for($board, 'board')->create();
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
        'ends_at' => '2026-01-31',
        'is_completed' => false,
    ]);

    Date::setTestNow('2026-02-02 10:00:00');

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'description' => 'Atualizado',
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task->overdue_notified_at)->not->toBeNull();

    Mail::assertQueued(TaskStatusMail::class, function (TaskStatusMail $mail) use ($user) {
        return $mail->type === 'overdue'
            && $mail->hasTo($user->email);
    });

    Date::setTestNow();
});

test('deleting a task dispatches an email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()->for($user)->for($board, 'board')->create();
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
    ]);

    $this->actingAs($user)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect();

    Mail::assertQueued(TaskStatusMail::class, function (TaskStatusMail $mail) use ($user) {
        return $mail->type === 'deleted'
            && $mail->hasTo($user->email);
    });
});
