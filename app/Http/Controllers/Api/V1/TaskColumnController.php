<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskColumnOrderRequest;
use App\Http\Requests\TaskColumnStoreRequest;
use App\Http\Resources\TaskColumnResource;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskColumnController extends Controller
{
    public function index(Request $request, TaskBoard $board): JsonResponse
    {
        if (! $request->user()->hasWorkspaceRole(
            $board->workspace_id,
            ['owner', 'admin', 'editor', 'member', 'viewer'],
        )) {
            abort(404);
        }

        $columns = $board->columns()->orderBy('sort_order')->get();

        return response()->json([
            'data' => TaskColumnResource::collection($columns),
        ]);
    }

    public function store(TaskColumnStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $boardId = $request->integer('task_board_id');
        $board = TaskBoard::query()->whereKey($boardId)->first();

        if (! $board) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['task_board_id' => ['Please select a valid board.']],
            ], 422);
        }

        if (! $user->hasWorkspaceRole($board->workspace_id, ['owner', 'admin', 'editor'])) {
            abort(404);
        }

        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskColumn::query()
            ->where('task_board_id', $boardId)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['This column already exists.']],
            ], 422);
        }

        $nextOrder = TaskColumn::query()
            ->where('task_board_id', $boardId)
            ->max('sort_order');

        $column = TaskColumn::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
            'task_board_id' => $boardId,
        ]);

        return response()->json([
            'data' => new TaskColumnResource($column),
        ], 201);
    }

    public function order(TaskColumnOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $boardId = $request->integer('task_board_id');
        $orderedIds = array_values(array_unique(
            $request->input('ordered_ids', []),
        ));

        $board = TaskBoard::query()->whereKey($boardId)->first();

        if (! $board) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['task_board_id' => ['Please select a valid board.']],
            ], 422);
        }

        if (! $user->hasWorkspaceRole($board->workspace_id, ['owner', 'admin', 'editor'])) {
            abort(404);
        }

        /** @var Collection<int, TaskColumn> $columns */
        $columns = TaskColumn::query()
            ->where('task_board_id', $boardId)
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        if ($columns->count() !== count($orderedIds)) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['ordered_ids' => ['Please provide a valid ordering.']],
            ], 422);
        }

        foreach ($orderedIds as $index => $columnId) {
            $columns[$columnId]->update([
                'sort_order' => $index + 1,
            ]);
        }

        return response()->json([
            'message' => 'Column order updated.',
        ]);
    }
}
