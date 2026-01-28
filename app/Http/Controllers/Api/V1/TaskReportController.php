<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskReportRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TaskBoard;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;

class TaskReportController extends Controller
{
    public function index(TaskReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $rangeStart = Date::parse($request->string('start')->toString())->startOfDay();
        $rangeEnd = Date::parse($request->string('end')->toString())->endOfDay();

        $board = $this->resolveBoard($user, $request->integer('task_board_id'));
        $columnIds = $board
            ? $board->columns()->pluck('id')->all()
            : [];

        $entries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->where('started_at', '<', $rangeEnd)
            ->where('ended_at', '>', $rangeStart)
            ->when($board, fn ($query) => $query->whereHas(
                'task',
                fn ($taskQuery) => $taskQuery->whereIn('task_column_id', $columnIds),
            ))
            ->with(['task.taskColumn'])
            ->orderBy('started_at')
            ->get();

        return response()->json([
            'data' => TimeEntryResource::collection($entries),
            'meta' => [
                'start' => $rangeStart->toDateString(),
                'end' => $rangeEnd->toDateString(),
                'board_id' => $board?->id,
            ],
        ]);
    }

    private function resolveBoard($user, ?int $boardId): ?TaskBoard
    {
        $query = $user->taskBoards()->orderBy('sort_order');

        if ($boardId) {
            $board = $query->whereKey($boardId)->first();

            if ($board) {
                return $board;
            }
        }

        return $query->first();
    }
}
