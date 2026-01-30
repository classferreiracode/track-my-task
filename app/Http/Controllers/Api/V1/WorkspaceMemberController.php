<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceMemberUpdateRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        if (! $request->user()->hasWorkspaceRole(
            $workspace->id,
            ['owner', 'admin', 'editor', 'member', 'viewer'],
        )) {
            abort(404);
        }

        $members = $workspace->members()
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
            ->values();

        return response()->json([
            'data' => $members,
        ]);
    }

    public function update(
        WorkspaceMemberUpdateRequest $request,
        Workspace $workspace,
        User $user,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor->hasWorkspaceRole($workspace->id, ['owner', 'admin'])) {
            abort(404);
        }

        $membership = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'Member not found.',
            ], 404);
        }

        if ($membership->role === 'owner' && $actor->id !== $user->id) {
            return response()->json([
                'message' => 'Owner role cannot be changed.',
            ], 422);
        }

        $membership->update($request->validated());

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $membership->role,
                'weekly_capacity_minutes' => $membership->weekly_capacity_minutes,
                'is_active' => $membership->is_active,
            ],
        ]);
    }
}
