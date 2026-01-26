<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $now = now();
        $dayStart = $now->startOfDay();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();

        $totalTasks = $user->tasks()->count();
        $completedTasks = $user->tasks()->where('is_completed', true)->count();
        $activeTimers = $user->timeEntries()->whereNull('ended_at')->count();

        $hoursToday = $this->sumUserSecondsForRange($user->id, $dayStart, $now);
        $hoursThisWeek = $this->sumUserSecondsForRange($user->id, $weekStart, $now);
        $hoursThisMonth = $this->sumUserSecondsForRange($user->id, $monthStart, $now);

        return Inertia::render('Dashboard', [
            'kpi' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'active_timers' => $activeTimers,
                'seconds_today' => $hoursToday,
                'seconds_week' => $hoursThisWeek,
                'seconds_month' => $hoursThisMonth,
                'day_start' => $dayStart->toDateString(),
                'week_start' => $weekStart->toDateString(),
                'month_start' => $monthStart->toDateString(),
                'as_of' => $now->toIso8601String(),
            ],
        ]);
    }

    private function sumUserSecondsForRange(
        int $userId,
        CarbonInterface $rangeStart,
        CarbonInterface $rangeEnd,
    ): int {
        $completedSeconds = TimeEntry::query()
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$rangeStart, $rangeEnd])
            ->sum('duration_seconds');

        $runningSeconds = TimeEntry::query()
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->get()
            ->sum(function (TimeEntry $entry) use ($rangeStart, $rangeEnd) {
                return $this->runningSecondsForRange($entry, $rangeStart, $rangeEnd);
            });

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
