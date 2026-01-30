<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkspaceStoreRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function store(WorkspaceStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $ownedCount = $user->ownedWorkspaces()->count();

        if ($ownedCount >= 2) {
            return back()->withErrors([
                'name' => 'Free plan allows up to 2 workspaces.',
            ]);
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
            return back()->withErrors([
                'name' => 'This workspace already exists.',
            ]);
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

        return back();
    }
}
