<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskTagStoreRequest;
use App\Http\Resources\TaskTagResource;
use App\Models\TaskTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = $request->user()
            ->taskTags()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => TaskTagResource::collection($tags),
        ]);
    }

    public function store(TaskTagStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $color = strtoupper($request->string('color')->toString());

        $existing = TaskTag::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['This tag already exists.']],
            ], 422);
        }

        $tag = $user->taskTags()->create([
            'name' => $name,
            'color' => $color,
        ]);

        return response()->json([
            'data' => new TaskTagResource($tag),
        ], 201);
    }
}
