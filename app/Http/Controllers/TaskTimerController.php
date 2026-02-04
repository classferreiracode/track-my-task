<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\PlanGate\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskTimerController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $workspace = $task->taskColumn?->board?->workspace;

        if ($workspace) {
            app(SubscriptionService::class)->assertCan($workspace, 'start_timer', [
                'user' => $request->user(),
            ]);
        }

        $activeEntry = $task->timeEntries()
            ->whereNull('ended_at')
            ->where('user_id', $request->user()->id)
            ->first();

        if ($activeEntry) {
            return back()->withErrors([
                'timer' => 'This task already has an active timer.',
            ]);
        }

        $task->timeEntries()->create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
        ]);

        return back();
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $activeEntry = $task->timeEntries()
            ->whereNull('ended_at')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $activeEntry) {
            return back()->withErrors([
                'timer' => 'There is no active timer to stop.',
            ]);
        }

        $endedAt = now();

        $activeEntry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $activeEntry->started_at->diffInSeconds($endedAt),
        ]);

        return back();
    }
}
