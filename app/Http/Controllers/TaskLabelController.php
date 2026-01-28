<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskLabelStoreRequest;
use App\Http\Requests\TaskLabelUpdateRequest;
use App\Http\Resources\TaskLabelResource;
use App\Models\TaskLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TaskLabelController extends Controller
{
    public function store(TaskLabelStoreRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $color = strtoupper($request->string('color')->toString());

        $existing = TaskLabel::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->exists();

        if ($existing) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This label already exists.',
                    'errors' => [
                        'name' => ['This label already exists.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'name' => 'This label already exists.',
            ]);
        }

        $label = $user->taskLabels()->create([
            'name' => $name,
            'color' => $color,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => TaskLabelResource::make($label),
            ]);
        }

        return back();
    }

    public function update(
        TaskLabelUpdateRequest $request,
        TaskLabel $label
    ): RedirectResponse|JsonResponse {
        $user = $request->user();

        if ($label->user_id !== $user->id) {
            abort(404);
        }

        $color = strtoupper($request->string('color')->toString());
        $label->update([
            'color' => $color,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => TaskLabelResource::make($label),
            ]);
        }

        return back();
    }
}
