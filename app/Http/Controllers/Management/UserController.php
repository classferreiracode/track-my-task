<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with([
                'workspaces' => function ($query) {
                    $query
                        ->withCount('boards')
                        ->with([
                            'boards' => fn ($boardQuery) => $boardQuery
                                ->orderBy('name')
                                ->limit(5),
                            'subscription',
                        ])
                        ->orderBy('name');
                },
            ])
            ->withCount('workspaces')
            ->withCount([
                'workspaceMemberships as active_memberships_count' => fn ($query) => $query
                    ->where('is_active', true),
            ])
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toDateString(),
                'workspaces_count' => $user->workspaces_count,
                'active_memberships_count' => $user->active_memberships_count,
                'is_active' => $user->active_memberships_count > 0,
                'workspaces' => $user->workspaces->map(fn ($workspace) => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug,
                    'plan_key' => $workspace->subscription?->plan_key ?? $workspace->plan,
                    'boards_count' => $workspace->boards_count,
                    'boards' => $workspace->boards->map(fn ($board) => [
                        'id' => $board->id,
                        'name' => $board->name,
                    ])->values(),
                ])->values(),
            ])
            ->values();

        return Inertia::render('management/Users', [
            'users' => $users,
        ]);
    }
}
