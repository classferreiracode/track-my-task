<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReorderRequest;
use App\Models\Task;
use App\Models\TaskColumn;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class TaskOrderController extends Controller
{
    public function update(TaskReorderRequest $request): RedirectResponse
    {
        $user = $request->user();
        $columnId = $request->integer('column_id');
        $orderedIds = $request->input('ordered_ids', []);

        $orderedIds = array_values(array_unique($orderedIds));

        $column = TaskColumn::query()
            ->with('board')
            ->whereKey($columnId)
            ->first();

        if (! $column) {
            return back()->withErrors([
                'column_id' => 'Please select a valid column.',
            ]);
        }

        if (! $user->hasWorkspaceRole($column->board->workspace_id, ['owner', 'admin', 'editor'])) {
            abort(403);
        }

        $doneSlugs = [
            'done',
            'concluido',
            'concluida',
            'concluidos',
            'concluidas',
        ];
        $isCompleted = in_array($column->slug, $doneSlugs, true);
        $completedAt = $isCompleted ? now() : null;
        $endedAt = $isCompleted ? now() : null;

        /** @var Collection<int, \App\Models\Task> $tasks */
        $tasks = Task::query()
            ->whereIn('id', $orderedIds)
            ->with(['taskColumn.board'])
            ->get()
            ->keyBy('id');

        $activeEntries = TimeEntry::query()
            ->whereNull('ended_at')
            ->where('user_id', $user->id)
            ->whereIn('task_id', $orderedIds)
            ->get()
            ->keyBy('task_id');

        if ($tasks->count() !== count($orderedIds)) {
            return back()->withErrors([
                'ordered_ids' => 'Please provide a valid ordering.',
            ]);
        }

        $workspaceId = $column->board->workspace_id;
        $invalidTask = $tasks->first(function ($task) use ($workspaceId) {
            return $task->taskColumn?->board?->workspace_id !== $workspaceId;
        });

        if ($invalidTask) {
            return back()->withErrors([
                'ordered_ids' => 'Please provide a valid ordering.',
            ]);
        }

        foreach ($orderedIds as $index => $taskId) {
            $task = $tasks[$taskId];

            if ($endedAt) {
                $activeEntry = $activeEntries->get($task->id);

                if ($activeEntry) {
                    $activeEntry->update([
                        'ended_at' => $endedAt,
                        'duration_seconds' => $activeEntry->started_at->diffInSeconds($endedAt),
                    ]);
                }
            }

            $task->update([
                'task_column_id' => $columnId,
                'sort_order' => $index + 1,
                'is_completed' => $isCompleted,
                'completed_at' => $completedAt,
            ]);
        }

        return back();
    }
}
