<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskBoardStoreRequest;
use App\Models\TaskBoard;
use App\Models\Workspace;
use App\Services\PlanGate\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TaskBoardController extends Controller
{
    public function store(TaskBoardStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);
        $workspaceId = $request->integer('workspace_id')
            ?: $user->workspaces()->orderBy('name')->value('workspaces.id');

        if (! $workspaceId) {
            return back()->withErrors([
                'workspace_id' => 'Please select a valid workspace.',
            ]);
        }

        $workspace = Workspace::query()->whereKey($workspaceId)->first();

        if (! $workspace) {
            return back()->withErrors([
                'workspace_id' => 'Please select a valid workspace.',
            ]);
        }

        if (! $user->hasWorkspaceRole($workspace->id, ['owner', 'admin', 'editor'])) {
            abort(403);
        }

        app(SubscriptionService::class)->assertCan($workspace, 'create_board');

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = TaskBoard::query()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return back()->withErrors([
                'name' => 'This board already exists.',
            ]);
        }

        $nextOrder = TaskBoard::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $workspace->boards()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $nextOrder ? $nextOrder + 1 : 1,
        ]);

        return back();
    }

    public function destroy(TaskBoard $board): RedirectResponse
    {
        $user = request()->user();
        $workspace = $board->workspace;

        if (! $workspace) {
            return back()->withErrors([
                'board' => 'Board not found.',
            ]);
        }

        $isOwnerOrAdmin = $user->hasWorkspaceRole($workspace->id, ['owner', 'admin']);
        $isCreator = $board->user_id === $user->id;

        if (! $isOwnerOrAdmin && ! $isCreator) {
            abort(403);
        }

        $board->delete();

        return back()->with('success', 'Board removido.');
    }
}
