<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReorderRequest;
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

        $column = $user->taskColumns()->whereKey($columnId)->first();

        if (! $column) {
            return back()->withErrors([
                'column_id' => 'Please select a valid column.',
            ]);
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
        $tasks = $user->tasks()
            ->whereIn('id', $orderedIds)
            ->with('activeTimeEntry')
            ->get()
            ->keyBy('id');

        if ($tasks->count() !== count($orderedIds)) {
            return back()->withErrors([
                'ordered_ids' => 'Please provide a valid ordering.',
            ]);
        }

        foreach ($orderedIds as $index => $taskId) {
            $task = $tasks[$taskId];

            if ($endedAt && $task->activeTimeEntry) {
                $task->activeTimeEntry->update([
                    'ended_at' => $endedAt,
                    'duration_seconds' => $task->activeTimeEntry->started_at->diffInSeconds($endedAt),
                ]);
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
