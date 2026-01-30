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
        if (! $request->user()->hasWorkspaceRole(
            $board->workspace_id,
            ['owner', 'admin', 'editor', 'member', 'viewer'],
        )) {
            abort(404);
        }

        $columnIds = $board->columns()->pluck('id')->all();

        $tasks = Task::query()
            ->whereIn('task_column_id', $columnIds)
            ->with(['taskColumn', 'activeTimeEntry', 'labels', 'tags', 'assignees'])
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
        $assignees = $data['assignees'] ?? null;

        unset($data['labels'], $data['tags'], $data['assignees']);

        if (! array_key_exists('task_column_id', $data)) {
            $boardId = (int) ($request->input('task_board_id') ?? 0);
            $board = TaskBoard::query()->whereKey($boardId)->first();

            if (! $board) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => ['task_board_id' => ['Please select a valid board.']],
                ], 422);
            }

            $column = $board->columns()->orderBy('sort_order')->first();
            $data['task_column_id'] = $column?->id;
        }

        $board = TaskBoard::query()
            ->with('workspace')
            ->whereHas('columns', function ($query) use ($data) {
                $query->where('id', $data['task_column_id'] ?? 0);
            })
            ->first();

        if (! $board || ! $request->user()->hasWorkspaceRole(
            $board->workspace_id,
            ['owner', 'admin', 'editor', 'member'],
        )) {
            abort(404);
        }

        $task = $request->user()->tasks()->create($data);

        if (is_array($labels)) {
            $task->labels()->sync($labels);
        }

        if (is_array($tags)) {
            $task->tags()->sync($tags);
        }

        if (is_array($assignees)) {
            $assignees = $board->workspace
                ? $board->workspace
                    ->members()
                    ->whereIn('users.id', $assignees)
                    ->pluck('users.id')
                    ->all()
                : [];

            $task->assignees()->sync(
                collect($assignees)->mapWithKeys(fn ($id) => [
                    $id => [
                        'assigned_by_user_id' => $request->user()->id,
                        'assigned_at' => now(),
                    ],
                ])->all(),
            );
        }

        $task->load(['taskColumn', 'activeTimeEntry', 'labels', 'tags', 'assignees']);

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function update(TaskUpdateRequest $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            $workspaceId = $task->taskColumn?->board?->workspace_id
                ?? $task->taskColumn()->with('board')->first()?->board?->workspace_id;

            if (! $workspaceId || ! $request->user()->hasWorkspaceRole(
                $workspaceId,
                ['owner', 'admin', 'editor', 'member'],
            )) {
                abort(404);
            }
        }

        $data = $request->validated();
        $labels = $data['labels'] ?? null;
        $tags = $data['tags'] ?? null;
        $assignees = $data['assignees'] ?? null;

        unset($data['labels'], $data['tags'], $data['assignees']);

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

        if (is_array($assignees)) {
            $workspaceId = $task->taskColumn?->board?->workspace_id
                ?? $task->taskColumn()->with('board')->first()?->board?->workspace_id;

            $assignees = $workspaceId
                ? $task->taskColumn
                    ? $task->taskColumn->board
                        ->workspace
                        ->members()
                        ->whereIn('users.id', $assignees)
                        ->pluck('users.id')
                        ->all()
                    : []
                : [];

            $task->assignees()->sync(
                collect($assignees)->mapWithKeys(fn ($id) => [
                    $id => [
                        'assigned_by_user_id' => $request->user()->id,
                        'assigned_at' => now(),
                    ],
                ])->all(),
            );
        }

        $task->load(['taskColumn', 'activeTimeEntry', 'labels', 'tags', 'assignees']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            $workspaceId = $task->taskColumn?->board?->workspace_id
                ?? $task->taskColumn()->with('board')->first()?->board?->workspace_id;

            if (! $workspaceId || ! $request->user()->hasWorkspaceRole(
                $workspaceId,
                ['owner', 'admin'],
            )) {
                abort(404);
            }
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
