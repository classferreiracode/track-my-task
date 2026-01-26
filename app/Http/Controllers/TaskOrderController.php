<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReorderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class TaskOrderController extends Controller
{
    public function update(TaskReorderRequest $request): RedirectResponse
    {
        $user = $request->user();
        $columnId = $request->integer('column_id');
        $orderedIds = $request->input('ordered_ids', []);

        $orderedIds = array_values(array_unique($orderedIds));

        /** @var Collection<int, \App\Models\Task> $tasks */
        $tasks = $user->tasks()
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        if ($tasks->count() !== count($orderedIds)) {
            return back()->withErrors([
                'ordered_ids' => 'Please provide a valid ordering.',
            ]);
        }

        foreach ($orderedIds as $index => $taskId) {
            $tasks[$taskId]->update([
                'task_column_id' => $columnId,
                'sort_order' => $index + 1,
            ]);
        }

        return back();
    }
}
