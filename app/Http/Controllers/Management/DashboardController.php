<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = \App\Models\Plan::query()
            ->select(['key', 'price_monthly'])
            ->get()
            ->mapWithKeys(fn ($plan) => [$plan->key => $plan->price_monthly])
            ->all();

        $subscriptionCounts = WorkspaceSubscription::query()
            ->where('status', 'active')
            ->selectRaw('plan_key, count(*) as total')
            ->groupBy('plan_key')
            ->pluck('total', 'plan_key')
            ->all();

        $mrr = collect($subscriptionCounts)
            ->reduce(
                fn (int $total, int $count, string $planKey) => $total + (($plans[$planKey] ?? 0) * $count),
                0,
            );

        $users = User::query()
            ->withCount('workspaces')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'workspaces_count' => $user->workspaces_count,
                'created_at' => $user->created_at?->toDateString(),
            ])
            ->values();

        $subscriptions = WorkspaceSubscription::query()
            ->with('workspace')
            ->latest('started_at')
            ->limit(20)
            ->get()
            ->map(fn (WorkspaceSubscription $subscription) => [
                'id' => $subscription->id,
                'workspace' => [
                    'id' => $subscription->workspace?->id,
                    'name' => $subscription->workspace?->name,
                ],
                'plan_key' => $subscription->plan_key,
                'status' => $subscription->status,
                'started_at' => $subscription->started_at?->toDateString(),
            ])
            ->values();

        return Inertia::render('management/Dashboard', [
            'kpi' => [
                'total_users' => User::query()->count(),
                'total_workspaces' => Workspace::query()->count(),
                'active_subscriptions' => array_sum($subscriptionCounts),
                'mrr' => $mrr,
                'total_tracked_seconds' => (int) TimeEntry::query()->sum('duration_seconds'),
                'total_tasks' => Task::query()->count(),
            ],
            'plans' => [
                'counts' => $subscriptionCounts,
                'prices' => $plans,
            ],
            'users' => $users,
            'subscriptions' => $subscriptions,
        ]);
    }
}
