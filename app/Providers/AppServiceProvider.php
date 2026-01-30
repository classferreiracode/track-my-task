<?php

namespace App\Providers;

use App\Actions\WorkspaceInvitations\AcceptWorkspaceInvitation;
use App\Models\Task;
use App\Models\WorkspaceInvitation;
use App\Observers\TaskObserver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureInvitationListeners();
        $this->configureObservers();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureInvitationListeners(): void
    {
        Event::listen([Registered::class, Login::class], function (object $event): void {
            $user = $event->user ?? null;

            if (! $user) {
                return;
            }

            $request = request();

            if (! $request->hasSession()) {
                return;
            }

            $token = $request->session()->get('invitation_token');

            if (! $token) {
                return;
            }

            $invitation = WorkspaceInvitation::query()
                ->where('token', $token)
                ->first();

            if (! $invitation) {
                $request->session()->forget('invitation_token');

                return;
            }

            if ($invitation->accepted_at || ($invitation->expires_at && $invitation->expires_at->isPast())) {
                $request->session()->forget('invitation_token');

                return;
            }

            if ($user->email !== $invitation->email) {
                $request->session()->flash('invitation', 'Este convite pertence a outro e-mail.');

                return;
            }

            $acceptInvitation = app(AcceptWorkspaceInvitation::class);

            if ($acceptInvitation->handle($user, $invitation)) {
                $request->session()->forget('invitation_token');
                $request->session()->flash('success', 'Convite aceito.');
                $request->session()->put('url.intended', route('tasks.index', [
                    'workspace' => $invitation->workspace_id,
                ]));
            }
        });
    }

    protected function configureObservers(): void
    {
        Task::observe(TaskObserver::class);
    }
}
