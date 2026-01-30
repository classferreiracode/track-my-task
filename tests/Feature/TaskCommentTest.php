<?php

use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Notifications\TaskMentioned;
use Illuminate\Support\Facades\Notification;

test('workspace members can comment and mention users', function () {
    Notification::fake();

    $user = User::factory()->create([
        'name' => 'Carlos Souza',
    ]);
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $mentioned = User::factory()->create([
        'name' => 'Ana Silva',
        'email' => 'ana@empresa.com',
    ]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $mentioned->id,
        'role' => 'member',
    ]);

    $board = TaskBoard::factory()->for($user)->create([
        'workspace_id' => $workspace->id,
    ]);
    $column = TaskColumn::factory()->for($user)->for($board, 'board')->create();
    $task = Task::factory()->for($user)->create([
        'task_column_id' => $column->id,
    ]);

    $response = $this->actingAs($user)->postJson(route('tasks.comments.store', $task), [
        'body' => 'Comentário com menção @ana e atualização.',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('task_comments', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Comentário com menção @ana e atualização.',
    ]);

    $commentId = $task->comments()->value('id');

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $commentId,
        'user_id' => $mentioned->id,
    ]);

    $this->assertDatabaseHas('task_activities', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'type' => 'commented',
    ]);

    Notification::assertSentTo($mentioned, TaskMentioned::class);
});
