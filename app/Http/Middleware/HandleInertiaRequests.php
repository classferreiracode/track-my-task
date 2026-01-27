<?php

namespace App\Http\Middleware;

use App\Models\TaskBoard;
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
        $boards = [];

        if ($user) {
            if (! $user->taskBoards()->exists()) {
                $user->taskBoards()->create([
                    'name' => 'Padrão',
                    'slug' => 'padrao',
                    'sort_order' => 1,
                ]);
            }

            $boards = TaskBoard::query()
                ->where('user_id', $user->id)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (TaskBoard $board) => [
                    'id' => $board->id,
                    'name' => $board->name,
                    'slug' => $board->slug,
                    'sort_order' => $board->sort_order,
                ])
                ->values()
                ->all();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'boards' => $boards,
        ];
    }
}
