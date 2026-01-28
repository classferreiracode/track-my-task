<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskTagStoreRequest;
use App\Http\Requests\TaskTagUpdateRequest;
use App\Http\Resources\TaskTagResource;
use App\Models\TaskTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TaskTagController extends Controller
{
    public function store(TaskTagStoreRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $color = strtoupper($request->string('color')->toString());

        $existing = TaskTag::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->exists();

        if ($existing) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This tag already exists.',
                    'errors' => [
                        'name' => ['This tag already exists.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'name' => 'This tag already exists.',
            ]);
        }

        $tag = $user->taskTags()->create([
            'name' => $name,
            'color' => $color,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => TaskTagResource::make($tag),
            ]);
        }

        return back();
    }

    public function update(
        TaskTagUpdateRequest $request,
        TaskTag $tag
    ): RedirectResponse|JsonResponse {
        $user = $request->user();

        if ($tag->user_id !== $user->id) {
            abort(404);
        }

        $color = strtoupper($request->string('color')->toString());
        $tag->update([
            'color' => $color,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => TaskTagResource::make($tag),
            ]);
        }

        return back();
    }
}
