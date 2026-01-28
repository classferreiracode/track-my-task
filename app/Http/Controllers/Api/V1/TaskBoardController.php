<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskBoardStoreRequest;
use App\Http\Resources\TaskBoardResource;
use App\Models\TaskBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskBoardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $boards = $request->user()
            ->taskBoards()
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => TaskBoardResource::collection($boards),
        ]);
    }

    public function store(TaskBoardStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskBoard::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['This board already exists.']],
            ], 422);
        }

        $nextOrder = TaskBoard::query()
            ->where('user_id', $user->id)
            ->max('sort_order');

        $board = $user->taskBoards()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
        ]);

        return response()->json([
            'data' => new TaskBoardResource($board),
        ], 201);
    }
}
