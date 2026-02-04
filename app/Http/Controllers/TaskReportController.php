<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReportRequest;
use App\Models\ExportLog;
use App\Models\TaskBoard;
use App\Models\TimeEntry;
use App\Services\PlanGate\SubscriptionService;
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

        $board = $this->resolveBoard($user, $request->integer('task_board_id'));
        $columnIds = $board
            ? $board->columns()->pluck('id')->all()
            : [];

        $workspace = $board?->workspace ?? $user->workspaces()->first();

        if ($workspace) {
            app(SubscriptionService::class)->assertCan($workspace, 'export');
        }

        $entries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->where('started_at', '<', $rangeEnd)
            ->where('ended_at', '>', $rangeStart)
            ->when($board, fn ($query) => $query->whereHas(
                'task',
                fn ($taskQuery) => $taskQuery->whereIn('task_column_id', $columnIds),
            ))
            ->with([
                'task.taskColumn',
            ])
            ->orderBy('started_at')
            ->get();

        $fileName = sprintf(
            'time-report-%s-to-%s.csv',
            $rangeStart->toDateString(),
            $rangeEnd->toDateString(),
        );

        if ($workspace) {
            ExportLog::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'board_id' => $board?->id,
                'exported_at' => now(),
            ]);
        }

        return response()->streamDownload(function () use ($entries, $rangeStart, $rangeEnd): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Task',
                'Status',
                'Day',
                'Play',
                'Pause',
                'Total (minutes)',
                'Total (hours)',
            ], ';');

            foreach ($entries as $entry) {
                $task = $entry->task;
                $statusValue = $task?->taskColumn?->name ?? 'Backlog';
                $entryStart = $entry->started_at;
                $entryEnd = $entry->ended_at;

                if (! $task || ! $entryStart || ! $entryEnd) {
                    continue;
                }

                $effectiveStart = $entryStart->greaterThan($rangeStart)
                    ? $entryStart
                    : $rangeStart;
                $effectiveEnd = $entryEnd->lessThan($rangeEnd)
                    ? $entryEnd
                    : $rangeEnd;

                if ($effectiveEnd->lessThanOrEqualTo($effectiveStart)) {
                    continue;
                }

                $seconds = $effectiveStart->diffInSeconds($effectiveEnd);
                $minutes = round($seconds / 60, 2);
                $hours = round($seconds / 3600, 2);

                fputcsv($handle, [
                    $task->title,
                    Str::of($statusValue)->title()->toString(),
                    $entryStart->format('d/m/Y'),
                    $entryStart->format('H:i:s'),
                    $entryEnd->format('H:i:s'),
                    $minutes,
                    $hours,
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveBoard($user, ?int $boardId): ?TaskBoard
    {
        if ($boardId) {
            $board = TaskBoard::query()->whereKey($boardId)->first();

            if ($board && $user->hasWorkspaceRole(
                $board->workspace_id,
                ['owner', 'admin', 'editor', 'member', 'viewer'],
            )) {
                return $board;
            }
        }

        return TaskBoard::query()
            ->whereIn('workspace_id', $user->workspaces()->pluck('workspaces.id'))
            ->orderBy('sort_order')
            ->first();
    }
}
