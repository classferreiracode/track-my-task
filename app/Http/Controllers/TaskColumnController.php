<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskColumnOrderRequest;
use App\Http\Requests\TaskColumnStoreRequest;
use App\Models\TaskColumn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskColumnController extends Controller
{
    public function store(TaskColumnStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $boardId = $request->integer('task_board_id');

        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskColumn::query()
            ->where('user_id', $user->id)
            ->where('task_board_id', $boardId)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return back()->withErrors([
                'name' => 'This column already exists.',
            ]);
        }

        $nextOrder = TaskColumn::query()
            ->where('user_id', $user->id)
            ->where('task_board_id', $boardId)
            ->max('sort_order');

        $user->taskColumns()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
            'task_board_id' => $boardId,
        ]);

        return back();
    }

    public function order(TaskColumnOrderRequest $request): RedirectResponse
    {
        $user = $request->user();
        $boardId = $request->integer('task_board_id');
        $orderedIds = array_values(array_unique(
            $request->input('ordered_ids', []),
        ));

        /** @var Collection<int, TaskColumn> $columns */
        $columns = $user->taskColumns()
            ->where('task_board_id', $boardId)
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        if ($columns->count() !== count($orderedIds)) {
            return back()->withErrors([
                'ordered_ids' => 'Please provide a valid ordering.',
            ]);
        }

        foreach ($orderedIds as $index => $columnId) {
            $columns[$columnId]->update([
                'sort_order' => $index + 1,
            ]);
        }

        return back();
    }
}
