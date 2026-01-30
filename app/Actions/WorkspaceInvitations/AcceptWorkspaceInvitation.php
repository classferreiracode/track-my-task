<?php

namespace App\Actions\WorkspaceInvitations;

use App\Models\User;
use App\Models\WorkspaceInvitation;

class AcceptWorkspaceInvitation
{
    public function handle(User $user, WorkspaceInvitation $invitation): bool
    {
        if ($invitation->accepted_at) {
            return false;
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return false;
        }

        if ($user->email !== $invitation->email) {
            return false;
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

        return true;
    }
}
