<?php

namespace App\Observers;

use App\Events\TaskActivityCreated;
use App\Events\TaskStatusUpdated;
use App\Mail\TaskStatusMail;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->logActivity($task, 'created', []);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $task->load(['taskColumn.board.workspace', 'assignees', 'user']);

        if ($task->wasChanged('task_column_id')) {
            $this->sendStatusMail($task, 'status_changed', [
                'status' => $task->taskColumn?->name,
            ]);

            $this->logActivity($task, 'status_changed', [
                'status' => $task->taskColumn?->name,
            ]);
        }

        if ($task->wasChanged('is_completed') && $task->is_completed) {
            $this->sendStatusMail($task, 'completed', [
                'completed_at' => $task->completed_at?->toIso8601String(),
            ]);

            $this->logActivity($task, 'completed', [
                'completed_at' => $task->completed_at?->toIso8601String(),
            ]);
        }

        if ($this->shouldNotifyOverdue($task)) {
            $this->markOverdueNotified($task);

            $this->sendStatusMail($task, 'overdue', [
                'due_date' => $task->ends_at?->toDateString(),
            ]);

            $this->logActivity($task, 'overdue', [
                'due_date' => $task->ends_at?->toDateString(),
            ]);
        }

        if ($task->wasChanged('task_column_id')
            || $task->wasChanged('sort_order')
            || $task->wasChanged('is_completed')) {
            TaskStatusUpdated::dispatch($task);
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $task->loadMissing(['taskColumn.board.workspace', 'assignees', 'user']);

        $this->sendStatusMail($task, 'deleted', []);
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }

    protected function shouldNotifyOverdue(Task $task): bool
    {
        if ($task->is_completed || ! $task->ends_at || $task->overdue_notified_at) {
            return false;
        }

        $dueAt = $task->ends_at instanceof CarbonInterface
            ? $task->ends_at->endOfDay()
            : Carbon::parse($task->ends_at)->endOfDay();

        return now()->greaterThan($dueAt);
    }

    protected function markOverdueNotified(Task $task): void
    {
        Task::withoutEvents(function () use ($task): void {
            $task->forceFill([
                'overdue_notified_at' => now(),
            ])->save();
        });
    }

    protected function sendStatusMail(Task $task, string $type, array $extra): void
    {
        $recipients = $this->recipientsFor($task);

        if ($recipients->isEmpty()) {
            return;
        }

        $payload = array_merge([
            'task_id' => $task->id,
            'task_title' => $task->title,
            'status' => $task->taskColumn?->name,
            'board' => $task->taskColumn?->board?->name,
            'workspace' => $task->taskColumn?->board?->workspace?->name,
            'due_date' => $task->ends_at?->toDateString(),
        ], $extra);

        Mail::to($recipients->pluck('email')->all())
            ->queue(new TaskStatusMail($type, $payload));
    }

    /**
     * @return Collection<int, \App\Models\User>
     */
    protected function recipientsFor(Task $task): Collection
    {
        return collect($task->assignees)
            ->filter()
            ->push($task->user)
            ->filter()
            ->unique('email')
            ->values();
    }

    protected function logActivity(Task $task, string $type, array $meta): void
    {
        $activity = $task->activities()->create([
            'user_id' => $this->resolveActorId(),
            'type' => $type,
            'meta' => $meta,
        ]);

        TaskActivityCreated::dispatch($activity);
    }

    protected function resolveActorId(): ?int
    {
        $user = request()->user();

        return $user?->id;
    }
}
