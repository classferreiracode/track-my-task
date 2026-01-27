<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $boards = $this->ensureDefaultBoards($request->user());
        $selectedBoard = $this->resolveBoard($boards, $request->integer('board'));
        $columns = $this->ensureDefaultColumns($request->user(), $selectedBoard);

        $now = now();
        $dayStart = $now->startOfDay();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();

        $columnIds = $columns->pluck('id')->all();

        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('task_column_id', $columnIds)
            ->with([
                'activeTimeEntry',
                'taskColumn',
                'timeEntries' => function ($query) use ($monthStart, $now) {
                    $query
                        ->whereNotNull('ended_at')
                        ->whereBetween('started_at', [$monthStart, $now])
                        ->orderBy('started_at');
                },
            ])
            ->orderBy('task_column_id')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Task $task) use ($columns, $dayStart, $weekStart, $monthStart, $now) {
                $column = $this->resolveTaskColumn($task, $columns);

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'column_id' => $task->task_column_id ?? $column?->id,
                    'sort_order' => $task->sort_order,
                    'is_completed' => $task->is_completed,
                    'completed_at' => $task->completed_at?->toIso8601String(),
                    'active_entry' => $task->activeTimeEntry
                        ? [
                            'id' => $task->activeTimeEntry->id,
                            'started_at' => $task->activeTimeEntry->started_at?->toIso8601String(),
                        ]
                        : null,
                    'totals' => [
                        'daily' => $this->sumDurationForRange($task, $dayStart, $now),
                        'weekly' => $this->sumDurationForRange($task, $weekStart, $now),
                        'monthly' => $this->sumDurationForRange($task, $monthStart, $now),
                    ],
                ];
            })
            ->values();

        return Inertia::render('tasks/Index', [
            'boards' => $boards->map(fn (TaskBoard $board) => [
                'id' => $board->id,
                'name' => $board->name,
                'slug' => $board->slug,
                'sort_order' => $board->sort_order,
            ])->values(),
            'selectedBoardId' => $selectedBoard?->id,
            'columns' => $columns->map(fn (TaskColumn $column) => [
                'id' => $column->id,
                'name' => $column->name,
                'slug' => $column->slug,
                'sort_order' => $column->sort_order,
            ])->values(),
            'tasks' => $tasks,
            'reporting' => [
                'day_start' => $dayStart->toDateString(),
                'week_start' => $weekStart->toDateString(),
                'month_start' => $monthStart->toDateString(),
                'as_of' => $now->toIso8601String(),
            ],
        ]);
    }

    public function store(TaskStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();

        if (! array_key_exists('task_column_id', $data)) {
            $boards = $this->ensureDefaultBoards($request->user());
            $selectedBoard = $this->resolveBoard(
                $boards,
                $request->integer('task_board_id') ?: $request->integer('board'),
            );
            $defaultColumn = $this->ensureDefaultColumns($request->user(), $selectedBoard)->first();
            $data['task_column_id'] = $defaultColumn?->id;
        }

        if (! array_key_exists('sort_order', $data)) {
            $data['sort_order'] = $request->user()
                ->tasks()
                ->where('task_column_id', $data['task_column_id'])
                ->max('sort_order') + 1;
        }

        $request->user()->tasks()->create($data);

        return back();
    }

    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        if (array_key_exists('task_column_id', $data)) {
            $column = $request->user()
                ->taskColumns()
                ->whereKey($data['task_column_id'])
                ->first();

            $this->syncCompletionForColumn($data, $column?->slug);
        } elseif (array_key_exists('is_completed', $data)) {
            $data['completed_at'] = $data['is_completed'] ? now() : null;
        }

        $task->fill($data);
        $task->save();

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back();
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCompletionForColumn(array &$data, ?string $columnSlug): void
    {
        if (! $columnSlug) {
            return;
        }

        if ($columnSlug === 'done') {
            $data['is_completed'] = true;
            $data['completed_at'] = now();

            return;
        }

        $data['is_completed'] = false;
        $data['completed_at'] = null;
    }

    /**
     * @return Collection<int, TaskColumn>
     */
    private function ensureDefaultColumns(User $user, ?TaskBoard $board): Collection
    {
        $defaults = [
            ['name' => 'Backlog', 'slug' => 'backlog'],
            ['name' => 'Em progresso', 'slug' => 'in_progress'],
            ['name' => 'Concluídas', 'slug' => 'done'],
        ];

        if (! $board) {
            return collect();
        }

        foreach ($defaults as $index => $column) {
            $user->taskColumns()->firstOrCreate(
                [
                    'slug' => $column['slug'],
                    'task_board_id' => $board->id,
                ],
                [
                    'name' => $column['name'],
                    'sort_order' => $index + 1,
                    'task_board_id' => $board->id,
                ],
            );
        }

        return $user->taskColumns()
            ->where('task_board_id', $board->id)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  Collection<int, TaskColumn>  $columns
     */
    private function resolveTaskColumn(Task $task, Collection $columns): ?TaskColumn
    {
        if ($task->task_column_id) {
            $column = $columns->firstWhere('id', $task->task_column_id);

            if ($column) {
                return $column;
            }
        }

        $column = $columns->first();

        if ($column) {
            $task->task_column_id = $column->id;
            $task->save();
        }

        return $column;
    }

    /**
     * @return Collection<int, TaskBoard>
     */
    private function ensureDefaultBoards(User $user): Collection
    {
        if (! $user->taskBoards()->exists()) {
            $user->taskBoards()->create([
                'name' => 'Padrão',
                'slug' => 'padrao',
                'sort_order' => 1,
            ]);
        }

        return $user->taskBoards()->orderBy('sort_order')->get();
    }

    /**
     * @param  Collection<int, TaskBoard>  $boards
     */
    private function resolveBoard(Collection $boards, ?int $boardId): ?TaskBoard
    {
        if ($boardId) {
            $board = $boards->firstWhere('id', $boardId);

            if ($board) {
                return $board;
            }
        }

        return $boards->first();
    }
}
