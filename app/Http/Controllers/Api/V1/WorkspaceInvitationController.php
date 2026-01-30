<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceInvitationStoreRequest;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceInvitationController extends Controller
{
    public function store(
        WorkspaceInvitationStoreRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $user = $request->user();

        if (! $user->hasWorkspaceRole($workspace->id, ['owner', 'admin'])) {
            abort(404);
        }

        $email = $request->string('email')->trim()->lower()->toString();
        $role = $request->string('role')->toString();

        $existingMember = $workspace->members()
            ->where('users.email', $email)
            ->exists();

        if ($existingMember) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['email' => ['This user is already a member.']],
            ], 422);
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
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['email' => ['This invitation is already pending.']],
            ], 422);
        }

        $invitation = $workspace->invitations()->create([
            'invited_by_user_id' => $user->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'token' => $invitation->token,
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->where('token', $token)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        if ($invitation->accepted_at) {
            return response()->json([
                'message' => 'Invitation already accepted.',
            ], 409);
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return response()->json([
                'message' => 'Invitation expired.',
            ], 410);
        }

        $user = $request->user();

        if (! $user || $user->email !== $invitation->email) {
            return response()->json([
                'message' => 'This invitation belongs to a different email.',
            ], 403);
        }

        $membership = $invitation->workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            $invitation->workspace->memberships()->create([
                'user_id' => $user->id,
                'role' => $invitation->role,
                'joined_at' => now(),
                'is_active' => true,
            ]);
        }

        $invitation->update([
            'accepted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Invitation accepted.',
        ]);
    }
}
