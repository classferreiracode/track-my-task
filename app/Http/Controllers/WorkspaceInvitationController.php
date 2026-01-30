<?php

namespace App\Http\Controllers;

use App\Actions\WorkspaceInvitations\AcceptWorkspaceInvitation;
use App\Http\Requests\WorkspaceInvitationStoreRequest;
use App\Mail\WorkspaceInvitationMail;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = WorkspaceInvitation::query()
            ->with(['workspace', 'inviter'])
            ->where('token', $token)
            ->first();

        $status = 'invalid';
        $user = $request->user();

        if ($invitation) {
            if ($invitation->accepted_at) {
                $status = 'accepted';
            } elseif ($invitation->expires_at && $invitation->expires_at->isPast()) {
                $status = 'expired';
            } elseif ($user && $user->email !== $invitation->email) {
                $status = 'mismatch';
            } else {
                $status = 'valid';
            }

            if (! $user && $status === 'valid') {
                $request->session()->put('invitation_token', $token);
                $request->session()->put('url.intended', route('tasks.index', [
                    'workspace' => $invitation->workspace_id,
                ]));
            }
        }

        return Inertia::render('invitations/Show', [
            'status' => $status,
            'token' => $token,
            'invitation' => $invitation ? [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at?->toIso8601String(),
                'accepted_at' => $invitation->accepted_at?->toIso8601String(),
                'workspace' => [
                    'id' => $invitation->workspace->id,
                    'name' => $invitation->workspace->name,
                ],
                'inviter' => [
                    'name' => $invitation->inviter?->name,
                    'email' => $invitation->inviter?->email,
                ],
            ] : null,
        ]);
    }

    public function store(
        WorkspaceInvitationStoreRequest $request,
        Workspace $workspace,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user->hasWorkspaceRole($workspace->id, ['owner', 'admin'])) {
            abort(403);
        }

        $email = $request->string('email')->trim()->lower()->toString();
        $role = $request->string('role')->toString();

        $existingMember = $workspace->members()
            ->where('users.email', $email)
            ->exists();

        if ($existingMember) {
            return back()->withErrors([
                'email' => 'This user is already a member.',
            ]);
        }

        $existingInvite = $workspace->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($existingInvite) {
            return back()->withErrors([
                'email' => 'This invitation is already pending.',
            ]);
        }

        $invitation = $workspace->invitations()->create([
            'invited_by_user_id' => $user->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->queue(new WorkspaceInvitationMail(
            $invitation,
            $workspace->name,
            $user->name,
        ));

        return back()->with('success', 'Convite enviado.');
    }

    public function accept(
        Request $request,
        string $token,
        AcceptWorkspaceInvitation $acceptInvitation,
    ): RedirectResponse {
        $invitation = WorkspaceInvitation::query()
            ->where('token', $token)
            ->first();

        if (! $invitation) {
            return redirect()->route('login');
        }

        if ($invitation->accepted_at) {
            return redirect()->route('dashboard')->with('success', 'Convite já aceito.');
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return redirect()->route('dashboard')->withErrors([
                'invitation' => 'Convite expirado.',
            ]);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $accepted = $acceptInvitation->handle($user, $invitation);

        if (! $accepted) {
            return redirect()->route('workspaces.invitations.show', $token)->withErrors([
                'invitation' => 'Este convite não corresponde ao seu e-mail.',
            ]);
        }

        return redirect()->route('tasks.index', [
            'workspace' => $invitation->workspace_id,
        ])->with('success', 'Convite aceito.');
    }
}
