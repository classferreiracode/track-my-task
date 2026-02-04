<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\PlanGate\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function plan(Request $request): Response
    {
        $user = $request->user();
        $workspaces = $user->workspaces()->orderBy('name')->get();
        $selectedWorkspace = $workspaces->firstWhere('id', $request->integer('workspace'))
            ?? $workspaces->first();

        if (! $selectedWorkspace) {
            return Inertia::render('settings/Plan', [
                'workspaces' => [],
                'selectedWorkspaceId' => null,
                'currentPlan' => null,
                'usage' => null,
                'limits' => null,
                'plans' => [],
            ]);
        }

        $service = app(SubscriptionService::class);
        $currentPlan = $service->currentPlan($selectedWorkspace);
        $usage = $service->usage($selectedWorkspace);
        $limits = $service->limits($selectedWorkspace);

        $plans = Plan::query()
            ->with('limits')
            ->orderByRaw("field(`key`, 'free', 'pro', 'business')")
            ->get()
            ->map(fn (Plan $plan) => [
                'key' => $plan->key,
                'name' => $plan->name,
                'description' => $plan->description,
                'limits' => $plan->limits
                    ->mapWithKeys(fn ($limit) => [$limit->limit_key => $limit->limit_value])
                    ->all(),
            ])
            ->values();

        return Inertia::render('settings/Plan', [
            'workspaces' => $workspaces->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $workspace->pivot?->role,
            ])->values(),
            'selectedWorkspaceId' => $selectedWorkspace->id,
            'currentPlan' => [
                'key' => $currentPlan->key,
                'name' => $currentPlan->name,
                'description' => $currentPlan->description,
            ],
            'usage' => $usage,
            'limits' => $limits,
            'plans' => $plans,
        ]);
    }
}
