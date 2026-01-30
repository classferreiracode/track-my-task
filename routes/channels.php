<?php

use App\Models\Task;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tasks.{taskId}', function ($user, $taskId) {
    $task = Task::query()
        ->with('taskColumn.board.workspace')
        ->whereKey($taskId)
        ->first();

    if (! $task) {
        return false;
    }

    $workspaceId = $task->taskColumn?->board?->workspace_id;

    if (! $workspaceId) {
        return false;
    }

    if (! $user->workspaces()->where('workspaces.id', $workspaceId)->exists()) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ];
});
