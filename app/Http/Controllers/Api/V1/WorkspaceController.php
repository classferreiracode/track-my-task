<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceStoreRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()
            ->workspaces()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function store(WorkspaceStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $ownedCount = $user->ownedWorkspaces()->count();

        if ($ownedCount >= 2) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['Free plan allows up to 2 workspaces.']],
            ], 422);
        }

        $name = $request->string('name')->trim()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $existing = Workspace::query()
            ->where('owner_user_id', $user->id)
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['name' => ['This workspace already exists.']],
            ], 422);
        }

        $workspace = $user->ownedWorkspaces()->create([
            'name' => $name,
            'slug' => $slug,
            'plan' => $request->string('plan')->toString() ?: 'free',
        ]);

        $workspace->memberships()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
        ], 201);
    }
}
