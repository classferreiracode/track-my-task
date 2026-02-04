<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Services\PlanGate\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceLimit
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $ability,
        string $workspaceParameter = 'workspace',
    ): Response {
        $workspace = $request->route($workspaceParameter);

        if (! $workspace) {
            $workspaceId = $request->integer($workspaceParameter);
            $workspace = $workspaceId ? Workspace::query()->whereKey($workspaceId)->first() : null;
        }

        if ($workspace instanceof Workspace) {
            app(SubscriptionService::class)->assertCan($workspace, $ability, [
                'user' => $request->user(),
            ]);
        }

        return $next($request);
    }
}
