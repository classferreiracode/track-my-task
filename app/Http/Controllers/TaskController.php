<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Mail\TaskStatusMail;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $workspaces = $this->ensureDefaultWorkspace($user);

        if ($workspaces->isEmpty()) {
            return redirect()->route('onboarding.show');
        }
        $selectedWorkspace = $this->resolveWorkspace(
            $workspaces,
            $request->integer('workspace'),
        );

        $boards = $this->ensureDefaultBoards($selectedWorkspace);
        $selectedBoard = $this->resolveBoard($boards, $request->integer('board'));
        $columns = $this->ensureDefaultColumns($selectedBoard);

        $now = now();
        $dayStart = $now->startOfDay();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();

        $columnIds = $columns->pluck('id')->all();

        $tasks = Task::query()
            ->whereIn('task_column_id', $columnIds)
            ->with([
                'activeTimeEntry',
                'assignees',
                'labels',
                'tags',
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
                    'priority' => $task->priority,
                    'starts_at' => $task->starts_at?->toDateString(),
                    'ends_at' => $task->ends_at?->toDateString(),
                    'column_id' => $task->task_column_id ?? $column?->id,
                    'sort_order' => $task->sort_order,
                    'is_completed' => $task->is_completed,
                    'completed_at' => $task->completed_at?->toIso8601String(),
                    'assignees' => $task->assignees->map(fn ($assignee) => [
                        'id' => $assignee->id,
                        'name' => $assignee->name,
                        'email' => $assignee->email,
                    ])->values(),
                    'labels' => $task->labels->map(fn ($label) => [
                        'id' => $label->id,
                        'name' => $label->name,
                        'color' => $label->color,
                    ])->values(),
                    'tags' => $task->tags->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ])->values(),
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
            'workspaces' => $workspaces->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $workspace->pivot?->role,
            ])->values(),
            'selectedWorkspaceId' => $selectedWorkspace?->id,
            'boards' => $boards->map(fn (TaskBoard $board) => [
                'id' => $board->id,
                'name' => $board->name,
                'slug' => $board->slug,
                'sort_order' => $board->sort_order,
                'user_id' => $board->user_id,
            ])->values(),
            'selectedBoardId' => $selectedBoard?->id,
            'columns' => $columns->map(fn (TaskColumn $column) => [
                'id' => $column->id,
                'name' => $column->name,
                'slug' => $column->slug,
                'sort_order' => $column->sort_order,
            ])->values(),
            'labels' => $user->taskLabels()
                ->orderBy('name')
                ->get()
                ->map(fn ($label) => [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                ])
                ->values(),
            'tags' => $user->taskTags()
                ->orderBy('name')
                ->get()
                ->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])
                ->values(),
            'members' => $selectedWorkspace
                ? $selectedWorkspace->members()
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->pivot?->role,
                        'weekly_capacity_minutes' => $member->pivot?->weekly_capacity_minutes,
                        'is_active' => $member->pivot?->is_active,
                    ])
                    ->values()
                : collect(),
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
        $labels = $data['labels'] ?? null;
        $tags = $data['tags'] ?? null;
        $assignees = $data['assignees'] ?? null;

        unset($data['labels'], $data['tags'], $data['assignees']);

        if (! array_key_exists('task_column_id', $data)) {
            $workspaces = $this->ensureDefaultWorkspace($request->user());

            if ($workspaces->isEmpty()) {
                return redirect()->route('onboarding.show');
            }
            $selectedWorkspace = $this->resolveWorkspace(
                $workspaces,
                $request->integer('workspace'),
            );
            $boards = $this->ensureDefaultBoards($selectedWorkspace);
            $selectedBoard = $this->resolveBoard(
                $boards,
                $request->integer('task_board_id') ?: $request->integer('board'),
            );
            $defaultColumn = $this->ensureDefaultColumns($selectedBoard)->first();
            $data['task_column_id'] = $defaultColumn?->id;
        }

        if (! array_key_exists('sort_order', $data)) {
            $data['sort_order'] = $request->user()
                ->tasks()
                ->where('task_column_id', $data['task_column_id'])
                ->max('sort_order') + 1;
        }

        $board = TaskBoard::query()
            ->with('workspace')
            ->whereHas('columns', function ($query) use ($data) {
                $query->where('id', $data['task_column_id'] ?? 0);
            })
            ->first();

        if (! $board || ! $request->user()->hasWorkspaceRole(
            $board->workspace_id,
            ['owner', 'admin', 'editor', 'member'],
        )) {
            abort(403);
        }

        $task = $request->user()->tasks()->create($data);

        if (is_array($labels)) {
            $task->labels()->sync($labels);
        }

        if (is_array($tags)) {
            $task->tags()->sync($tags);
        }

        if (is_array($assignees)) {
            $workspaceId = $this->workspaceIdForTask($task);
            $assignees = $this->filterWorkspaceMembers($workspaceId, $assignees);
            $task->assignees()->sync(
                collect($assignees)->mapWithKeys(fn ($id) => [
                    $id => [
                        'assigned_by_user_id' => $request->user()->id,
                        'assigned_at' => now(),
                    ],
                ])->all(),
            );

            $task->activities()->create([
                'user_id' => $request->user()->id,
                'type' => 'assigned',
                'meta' => [
                    'assignees' => $assignees,
                ],
            ]);

            $this->sendAssignmentMail($task, $assignees, $board);
        }

        return back();
    }

    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();
        $labels = $data['labels'] ?? null;
        $tags = $data['tags'] ?? null;
        $assignees = $data['assignees'] ?? null;

        unset($data['labels'], $data['tags'], $data['assignees']);

        if (array_key_exists('task_column_id', $data)) {
            $column = TaskColumn::query()
                ->with('board')
                ->whereKey($data['task_column_id'])
                ->first();

            if (! $column || ! $request->user()->hasWorkspaceRole(
                $column->board->workspace_id,
                ['owner', 'admin', 'editor', 'member'],
            )) {
                abort(403);
            }

            $this->syncCompletionForColumn($data, $column?->slug);
        } elseif (array_key_exists('is_completed', $data)) {
            $data['completed_at'] = $data['is_completed'] ? now() : null;
        }

        if (($data['is_completed'] ?? false) === true) {
            $this->stopActiveTimer($task);
        }

        $task->fill($data);
        $task->save();

        if (is_array($labels)) {
            $task->labels()->sync($labels);
        }

        if (is_array($tags)) {
            $task->tags()->sync($tags);
        }

        if (is_array($assignees)) {
            $workspaceId = $this->workspaceIdForTask($task);
            $assignees = $this->filterWorkspaceMembers($workspaceId, $assignees);
            $task->assignees()->sync(
                collect($assignees)->mapWithKeys(fn ($id) => [
                    $id => [
                        'assigned_by_user_id' => $request->user()->id,
                        'assigned_at' => now(),
                    ],
                ])->all(),
            );
        }

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

    private function stopActiveTimer(Task $task): void
    {
        $activeEntry = $task->activeTimeEntry()->first();

        if (! $activeEntry) {
            return;
        }

        $endedAt = now();

        $activeEntry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $activeEntry->started_at->diffInSeconds($endedAt),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCompletionForColumn(array &$data, ?string $columnSlug): void
    {
        if (! $columnSlug) {
            return;
        }

        $doneSlugs = [
            'done',
            'concluido',
            'concluida',
            'concluidos',
            'concluidas',
        ];

        if (in_array($columnSlug, $doneSlugs, true)) {
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
    private function ensureDefaultColumns(?TaskBoard $board): Collection
    {
        $defaults = [
            ['name' => 'Backlog', 'slug' => 'backlog'],
            ['name' => 'Em progresso', 'slug' => 'em-progresso'],
            ['name' => 'Concluído', 'slug' => 'concluido'],
        ];

        if (! $board) {
            return collect();
        }

        foreach ($defaults as $index => $column) {
            TaskColumn::query()->firstOrCreate(
                [
                    'slug' => $column['slug'],
                    'task_board_id' => $board->id,
                ],
                [
                    'user_id' => $board->user_id,
                    'name' => $column['name'],
                    'sort_order' => $index + 1,
                    'task_board_id' => $board->id,
                ],
            );
        }

        return TaskColumn::query()
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
    private function ensureDefaultBoards(?Workspace $workspace): Collection
    {
        if (! $workspace) {
            return collect();
        }

        return $workspace->boards()->orderBy('sort_order')->get();
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

    /**
     * @return Collection<int, Workspace>
     */
    private function ensureDefaultWorkspace(User $user): Collection
    {
        return $user->workspaces()->orderBy('name')->get();
    }

    /**
     * @param  Collection<int, Workspace>  $workspaces
     */
    private function resolveWorkspace(Collection $workspaces, ?int $workspaceId): ?Workspace
    {
        if ($workspaceId) {
            $workspace = $workspaces->firstWhere('id', $workspaceId);
            if ($workspace) {
                return $workspace;
            }
        }

        return $workspaces->first();
    }

    private function workspaceIdForTask(Task $task): ?int
    {
        $taskColumn = $task->taskColumn()->with('board')->first();

        return $taskColumn?->board?->workspace_id;
    }

    /**
     * @param  array<int, int>  $assignees
     * @return array<int, int>
     */
    private function filterWorkspaceMembers(?int $workspaceId, array $assignees): array
    {
        if (! $workspaceId || $assignees === []) {
            return [];
        }

        $workspace = Workspace::query()->whereKey($workspaceId)->first();

        if (! $workspace) {
            return [];
        }

        return $workspace->members()
            ->whereIn('users.id', $assignees)
            ->pluck('users.id')
            ->all();
    }

    /**
     * @param  array<int, int>  $assigneeIds
     */
    private function sendAssignmentMail(Task $task, array $assigneeIds, ?TaskBoard $board): void
    {
        if ($assigneeIds === []) {
            return;
        }

        $recipients = User::query()
            ->whereIn('id', $assigneeIds)
            ->pluck('email')
            ->all();

        if ($recipients === []) {
            return;
        }

        $task->loadMissing(['taskColumn.board.workspace']);

        $payload = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'status' => $task->taskColumn?->name,
            'board' => $board?->name ?? $task->taskColumn?->board?->name,
            'workspace' => $board?->workspace?->name ?? $task->taskColumn?->board?->workspace?->name,
            'due_date' => $task->ends_at?->toDateString(),
            'assigned_by' => $task->user?->name,
        ];

        Mail::to($recipients)->queue(new TaskStatusMail('assigned', $payload));
    }
}
