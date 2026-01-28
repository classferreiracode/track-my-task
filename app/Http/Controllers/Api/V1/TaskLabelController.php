<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskLabelStoreRequest;
use App\Http\Resources\TaskLabelResource;
use App\Models\TaskLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskLabelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $labels = $request->user()
            ->taskLabels()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => TaskLabelResource::collection($labels),
        ]);
    }

    public function store(TaskLabelStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $color = strtoupper($request->string('color')->toString());

        $existing = TaskLabel::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['This label already exists.']],
            ], 422);
        }

        $label = $user->taskLabels()->create([
            'name' => $name,
            'color' => $color,
        ]);

        return response()->json([
            'data' => new TaskLabelResource($label),
        ], 201);
    }
}
