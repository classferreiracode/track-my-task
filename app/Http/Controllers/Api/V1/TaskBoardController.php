<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskBoardStoreRequest;
use App\Http\Resources\TaskBoardResource;
use App\Models\TaskBoard;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskBoardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaceId = $request->integer('workspace_id')
            ?: $request->user()->workspaces()->orderBy('name')->value('workspaces.id');

        if (! $workspaceId) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['workspace_id' => ['Please select a valid workspace.']],
            ], 422);
        }

        $workspace = Workspace::query()->whereKey($workspaceId)->first();

        if (! $workspace || ! $request->user()->hasWorkspaceRole(
            $workspace->id,
            ['owner', 'admin', 'editor', 'member', 'viewer'],
        )) {
            abort(404);
        }

        $boards = $workspace->boards()->orderBy('sort_order')->get();

        return response()->json([
            'data' => TaskBoardResource::collection($boards),
        ]);
    }

    public function store(TaskBoardStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);
        $workspaceId = $request->integer('workspace_id')
            ?: $user->workspaces()->orderBy('name')->value('workspaces.id');

        if (! $workspaceId) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['workspace_id' => ['Please select a valid workspace.']],
            ], 422);
        }

        $workspace = Workspace::query()->whereKey($workspaceId)->first();

        if (! $workspace || ! $user->hasWorkspaceRole(
            $workspace->id,
            ['owner', 'admin', 'editor'],
        )) {
            abort(404);
        }

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskBoard::query()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['This board already exists.']],
            ], 422);
        }

        $nextOrder = TaskBoard::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $board = $workspace->boards()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
        ]);

        return response()->json([
            'data' => new TaskBoardResource($board),
        ], 201);
    }
}
