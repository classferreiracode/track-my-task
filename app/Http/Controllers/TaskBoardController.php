<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskBoardStoreRequest;
use App\Models\TaskBoard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TaskBoardController extends Controller
{
    public function store(TaskBoardStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskBoard::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return back()->withErrors([
                'name' => 'This board already exists.',
            ]);
        }

        $nextOrder = TaskBoard::query()
            ->where('user_id', $user->id)
            ->max('sort_order');

        $user->taskBoards()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
        ]);

        return back();
    }
}
