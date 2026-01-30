<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('onboarding.*', 'workspaces.invitations.show', 'workspaces.invitations.accept')) {
            return $next($request);
        }

        if ($user->workspaces()->exists()) {
            return $next($request);
        }

        $token = $request->session()->get('invitation_token');

        if ($token) {
            return redirect()->route('workspaces.invitations.show', $token);
        }

        return redirect()->route('onboarding.show');
    }
}
