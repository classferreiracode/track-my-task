<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskReorderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class TaskOrderController extends Controller
{
    public function update(TaskReorderRequest $request): JsonResponse
    {
        $user = $request->user();
        $columnId = $request->integer('column_id');
        $orderedIds = $request->input('ordered_ids', []);

        $orderedIds = array_values(array_unique($orderedIds));

        $column = $user->taskColumns()->whereKey($columnId)->first();

        if (! $column) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['column_id' => ['Please select a valid column.']],
            ], 422);
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
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['ordered_ids' => ['Please provide a valid ordering.']],
            ], 422);
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

        return response()->json([
            'message' => 'Task order updated.',
        ]);
    }
}
