<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $workspaces = $user->workspaces()->orderBy('name')->get();

        if ($workspaces->isEmpty()) {
            return redirect()->route('onboarding.show');
        }

        $selectedWorkspace = $this->resolveWorkspace(
            $workspaces,
            $request->integer('workspace'),
        );

        return Inertia::render('teams/Index', [
            'workspaces' => $workspaces->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $workspace->pivot?->role,
            ])->values(),
            'selectedWorkspaceId' => $selectedWorkspace?->id,
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
            'invitations' => $selectedWorkspace
                ? $selectedWorkspace->invitations()
                    ->latest()
                    ->get()
                    ->map(fn ($invite) => [
                        'id' => $invite->id,
                        'email' => $invite->email,
                        'role' => $invite->role,
                        'token' => $invite->token,
                        'accepted_at' => $invite->accepted_at?->toIso8601String(),
                        'expires_at' => $invite->expires_at?->toIso8601String(),
                    ])
                    ->values()
                : collect(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Workspace>  $workspaces
     */
    private function resolveWorkspace($workspaces, ?int $workspaceId): ?Workspace
    {
        if ($workspaceId) {
            $workspace = $workspaces->firstWhere('id', $workspaceId);
            if ($workspace) {
                return $workspace;
            }
        }

        return $workspaces->first();
    }
}
