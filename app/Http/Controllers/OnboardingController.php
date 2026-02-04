<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnboardingStoreRequest;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $user = request()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->workspaces()->exists()) {
            return redirect()->route('tasks.index');
        }

        return Inertia::render('onboarding/Setup');
    }

    public function store(OnboardingStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $workspaceName = $request->string('workspace_name')->trim()->toString();
        $boardName = $request->string('board_name')->trim()->toString();

        $workspaceSlug = Str::slug($workspaceName);
        if ($workspaceSlug === '') {
            $workspaceSlug = Str::slug(Str::random(8));
        }

        if ($user->ownedWorkspaces()->where('slug', $workspaceSlug)->exists()) {
            $workspaceSlug = $workspaceSlug.'-'.Str::lower(Str::random(4));
        }

        $workspace = $user->ownedWorkspaces()->create([
            'name' => $workspaceName,
            'slug' => $workspaceSlug,
            'plan' => 'free',
        ]);

        $workspace->subscription()->create([
            'plan_key' => 'free',
            'status' => 'active',
            'started_at' => now(),
        ]);

        $workspace->memberships()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $boardSlug = Str::slug($boardName);
        if ($boardSlug === '') {
            $boardSlug = Str::slug(Str::random(8));
        }

        if (TaskBoard::query()->where('user_id', $user->id)->where('slug', $boardSlug)->exists()) {
            $boardSlug = $boardSlug.'-'.Str::lower(Str::random(4));
        }

        $board = $workspace->boards()->create([
            'user_id' => $user->id,
            'name' => $boardName,
            'slug' => $boardSlug,
            'sort_order' => 1,
        ]);

        $this->createDefaultColumns($board);

        return redirect()->route('tasks.index', [
            'workspace' => $workspace->id,
            'board' => $board->id,
        ])->with('success', 'Workspace criado com sucesso.');
    }

    private function createDefaultColumns(TaskBoard $board): Collection
    {
        $defaults = [
            ['name' => 'Backlog', 'slug' => 'backlog'],
            ['name' => 'Em progresso', 'slug' => 'em-progresso'],
            ['name' => 'Concluído', 'slug' => 'concluido'],
        ];

        foreach ($defaults as $index => $column) {
            TaskColumn::query()->create([
                'user_id' => $board->user_id,
                'name' => $column['name'],
                'slug' => $column['slug'],
                'sort_order' => $index + 1,
                'task_board_id' => $board->id,
            ]);
        }

        return TaskColumn::query()
            ->where('task_board_id', $board->id)
            ->orderBy('sort_order')
            ->get();
    }
}
