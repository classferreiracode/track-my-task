<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkspaceMemberUpdateRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceMemberChanged;
use Illuminate\Http\RedirectResponse;

class WorkspaceMemberController extends Controller
{
    public function update(
        WorkspaceMemberUpdateRequest $request,
        Workspace $workspace,
        User $user,
    ): RedirectResponse {
        $actor = $request->user();

        if (! $actor->hasWorkspaceRole($workspace->id, ['owner', 'admin'])) {
            abort(403);
        }

        $membership = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return back()->withErrors([
                'member' => 'Member not found.',
            ]);
        }

        if ($membership->role === 'owner' && $actor->id !== $user->id) {
            $requestedRole = $request->filled('role')
                ? $request->string('role')->toString()
                : null;

            if ($requestedRole && $requestedRole !== 'owner') {
                return back()->withErrors([
                    'role' => 'Owner role cannot be changed.',
                ]);
            }
        }

        $membership->update($request->validated());

        return back();
    }

    public function destroy(Workspace $workspace, User $user): RedirectResponse
    {
        $actor = request()->user();

        if (! $actor->hasWorkspaceRole($workspace->id, ['owner', 'admin'])) {
            abort(403);
        }

        $membership = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return back()->withErrors([
                'member' => 'Member not found.',
            ]);
        }

        if ($membership->role === 'owner') {
            return back()->withErrors([
                'member' => 'Owner cannot be removed.',
            ]);
        }

        $membership->delete();

        $user->notify(new WorkspaceMemberChanged($workspace, 'removed', $actor));

        return back()->with('success', 'Membro removido.');
    }

    public function leave(Workspace $workspace): RedirectResponse
    {
        $user = request()->user();

        $membership = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return back()->withErrors([
                'member' => 'Member not found.',
            ]);
        }

        if ($membership->role === 'owner') {
            return back()->withErrors([
                'member' => 'Owner cannot leave the workspace.',
            ]);
        }

        $membership->delete();

        $user->notify(new WorkspaceMemberChanged($workspace, 'left', $user));

        return redirect()->route('dashboard')->with('success', 'Você saiu do workspace.');
    }
}
