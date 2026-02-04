<?php

namespace App\Http\Middleware;

use App\Models\TaskBoard;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user && ! $user instanceof User) {
            $user = null;
        }
        $boards = [];
        $workspaces = collect();
        $notifications = collect();
        $unreadCount = 0;

        if ($user) {
            $workspaces = $user->workspaces()->orderBy('name')->get();
            $activeWorkspace = $workspaces->first();

            $notifications = $user->notifications()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ]);

            $unreadCount = $user->unreadNotifications()->count();

            if ($activeWorkspace) {
                $boards = TaskBoard::query()
                    ->where('workspace_id', $activeWorkspace->id)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (TaskBoard $board) => [
                        'id' => $board->id,
                        'name' => $board->name,
                        'slug' => $board->slug,
                        'sort_order' => $board->sort_order,
                        'user_id' => $board->user_id,
                    ])
                    ->values()
                    ->all();
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'invitation' => $request->session()->get('invitation'),
                'plan_limit' => $request->session()->get('plan_limit'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'boards' => $boards,
            'workspaces' => $workspaces->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $workspace->pivot?->role,
            ])->values(),
            'notifications' => [
                'items' => $notifications->values(),
                'unread_count' => $unreadCount,
            ],
        ];
    }
}
