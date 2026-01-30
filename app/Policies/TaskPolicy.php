<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        $workspaceId = $this->workspaceIdForTask($task);

        if (! $workspaceId) {
            return false;
        }

        return $user->hasWorkspaceRole(
            $workspaceId,
            ['owner', 'admin', 'editor', 'member', 'viewer'],
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        $workspaceId = $this->workspaceIdForTask($task);

        if (! $workspaceId) {
            return false;
        }

        if ($user->hasWorkspaceRole($workspaceId, ['owner', 'admin', 'editor'])) {
            return true;
        }

        if ($user->hasWorkspaceRole($workspaceId, ['member'])) {
            if ($task->user_id === $user->id) {
                return true;
            }

            return $task->assignees()
                ->whereKey($user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        $workspaceId = $this->workspaceIdForTask($task);

        if (! $workspaceId) {
            return false;
        }

        return $user->hasWorkspaceRole($workspaceId, ['owner', 'admin']);
    }

    private function workspaceIdForTask(Task $task): ?int
    {
        $column = $task->taskColumn()->with('board')->first();

        return $column?->board?->workspace_id;
    }
}
