<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReportRequest;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskReportController extends Controller
{
    public function export(TaskReportRequest $request): StreamedResponse
    {
        $user = $request->user();
        $rangeStart = Date::parse($request->string('start')->toString())->startOfDay();
        $rangeEnd = Date::parse($request->string('end')->toString())->endOfDay();

        $tasks = Task::query()
            ->where('user_id', $user->id)
            ->with([
                'activeTimeEntry',
                'taskColumn',
                'timeEntries' => function ($query) use ($rangeStart, $rangeEnd) {
                    $query
                        ->whereNotNull('ended_at')
                        ->whereBetween('started_at', [$rangeStart, $rangeEnd])
                        ->orderBy('started_at');
                },
            ])
            ->orderBy('task_column_id')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        $fileName = sprintf(
            'time-report-%s-to-%s.csv',
            $rangeStart->toDateString(),
            $rangeEnd->toDateString(),
        );

        return response()->streamDownload(function () use ($tasks, $rangeStart, $rangeEnd): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Task',
                'Status',
                'Total (minutes)',
                'Total (hours)',
            ], ';');

            foreach ($tasks as $task) {
                $seconds = $this->sumDurationForRange($task, $rangeStart, $rangeEnd);
                $minutes = round($seconds / 60, 2);
                $hours = round($seconds / 3600, 2);
                $statusValue = $task->taskColumn?->name ?? 'Backlog';

                fputcsv($handle, [
                    $task->title,
                    Str::of($statusValue)->title()->toString(),
                    $minutes,
                    $hours,
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function sumDurationForRange(
        Task $task,
        CarbonInterface $rangeStart,
        CarbonInterface $rangeEnd,
    ): int {
        $completedSeconds = $task->timeEntries
            ->filter(function (TimeEntry $entry) use ($rangeStart, $rangeEnd) {
                return $entry->started_at->betweenIncluded($rangeStart, $rangeEnd);
            })
            ->sum('duration_seconds');

        $runningSeconds = 0;
        $activeEntry = $task->activeTimeEntry;

        if ($activeEntry) {
            $runningSeconds = $this->runningSecondsForRange(
                $activeEntry,
                $rangeStart,
                $rangeEnd,
            );
        }

        return $completedSeconds + $runningSeconds;
    }

    private function runningSecondsForRange(
        TimeEntry $entry,
        CarbonInterface $rangeStart,
        CarbonInterface $rangeEnd,
    ): int {
        $entryStart = $entry->started_at;

        if ($entryStart->greaterThanOrEqualTo($rangeEnd)) {
            return 0;
        }

        $effectiveStart = $entryStart->greaterThan($rangeStart)
            ? $entryStart
            : $rangeStart;

        if ($effectiveStart->greaterThanOrEqualTo($rangeEnd)) {
            return 0;
        }

        return $effectiveStart->diffInSeconds($rangeEnd);
    }
}
