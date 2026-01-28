<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTimerController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(404);
        }

        $activeEntry = $task->activeTimeEntry()->first();

        if ($activeEntry) {
            return response()->json([
                'message' => 'This task already has an active timer.',
            ], 422);
        }

        $entry = $task->timeEntries()->create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Timer started.',
            'data' => [
                'id' => $entry->id,
                'started_at' => $entry->started_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(404);
        }

        $activeEntry = $task->activeTimeEntry()->first();

        if (! $activeEntry) {
            return response()->json([
                'message' => 'There is no active timer to stop.',
            ], 422);
        }

        $endedAt = now();

        $activeEntry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $activeEntry->started_at->diffInSeconds($endedAt),
        ]);

        return response()->json([
            'message' => 'Timer stopped.',
        ]);
    }
}
