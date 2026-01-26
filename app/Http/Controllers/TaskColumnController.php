<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskColumnStoreRequest;
use App\Models\TaskColumn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TaskColumnController extends Controller
{
    public function store(TaskColumnStoreRequest $request): RedirectResponse
    {
        $user = $request->user();

        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskColumn::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return back()->withErrors([
                'name' => 'This column already exists.',
            ]);
        }

        $nextOrder = TaskColumn::query()
            ->where('user_id', $user->id)
            ->max('sort_order');

        $user->taskColumns()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
        ]);

        return back();
    }
}
