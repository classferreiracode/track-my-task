<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, TaskBoard $board): JsonResponse
    {
        if ($board->user_id !== $request->user()->id) {
            abort(404);
        }

        $columnIds = $board->columns()->pluck('id')->all();

        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('task_column_id', $columnIds)
            ->with(['taskColumn', 'activeTimeEntry', 'labels', 'tags'])
            ->orderBy('task_column_id')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
        ]);
    }

    public function store(TaskStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $labels = $data['labels'] ?? null;
        $tags = $data['tags'] ?? null;

        unset($data['labels'], $data['tags']);

        $task = $request->user()->tasks()->create($data);

        if (is_array($labels)) {
            $task->labels()->sync($labels);
        }

        if (is_array($tags)) {
            $task->tags()->sync($tags);
        }

        $task->load(['taskColumn', 'activeTimeEntry', 'labels', 'tags']);

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function update(TaskUpdateRequest $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(404);
        }

        $data = $request->validated();
        $labels = $data['labels'] ?? null;
        $tags = $data['tags'] ?? null;

        unset($data['labels'], $data['tags']);

        if (array_key_exists('task_column_id', $data)) {
            $column = TaskColumn::query()
                ->where('user_id', $request->user()->id)
                ->whereKey($data['task_column_id'])
                ->first();

            $this->syncCompletionForColumn($data, $column?->slug);
        } elseif (array_key_exists('is_completed', $data)) {
            $data['completed_at'] = $data['is_completed'] ? now() : null;
        }

        if (($data['is_completed'] ?? false) === true) {
            $this->stopActiveTimer($task);
        }

        $task->fill($data);
        $task->save();

        if (is_array($labels)) {
            $task->labels()->sync($labels);
        }

        if (is_array($tags)) {
            $task->tags()->sync($tags);
        }

        $task->load(['taskColumn', 'activeTimeEntry', 'labels', 'tags']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(404);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCompletionForColumn(array &$data, ?string $columnSlug): void
    {
        if (! $columnSlug) {
            return;
        }

        $doneSlugs = [
            'done',
            'concluido',
            'concluida',
            'concluidos',
            'concluidas',
        ];

        if (in_array($columnSlug, $doneSlugs, true)) {
            $data['is_completed'] = true;
            $data['completed_at'] = now();

            return;
        }

        $data['is_completed'] = false;
        $data['completed_at'] = null;
    }

    private function stopActiveTimer(Task $task): void
    {
        $activeEntry = $task->activeTimeEntry()->first();

        if (! $activeEntry) {
            return;
        }

        $endedAt = now();

        $activeEntry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $activeEntry->started_at->diffInSeconds($endedAt),
        ]);
    }
}
