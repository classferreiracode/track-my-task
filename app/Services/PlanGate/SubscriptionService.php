<?php

namespace App\Services\PlanGate;

use App\Exceptions\PlanLimitExceededException;
use App\Models\ExportLog;
use App\Models\Plan;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;

class SubscriptionService
{
    /**
     * @return array<string, int|null>
     */
    public function limits(Workspace $workspace): array
    {
        $plan = $this->currentPlan($workspace);

        $limits = $plan->limits()
            ->get()
            ->mapWithKeys(fn ($limit) => [$limit->limit_key => $limit->limit_value])
            ->all();

        if (! array_key_exists('max_active_timers_per_user', $limits)) {
            $limits['max_active_timers_per_user'] = 1;
        }

        return $limits;
    }

    /**
     * @return array<string, int>
     */
    public function usage(Workspace $workspace): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return [
            'members_count' => $workspace->memberships()->count(),
            'boards_count' => $workspace->boards()->count(),
            'exports_count_month' => ExportLog::query()
                ->where('workspace_id', $workspace->id)
                ->whereBetween('exported_at', [$monthStart, $monthEnd])
                ->count(),
        ];
    }

    public function currentPlan(Workspace $workspace): Plan
    {
        $planKey = $workspace->subscription?->plan_key ?? $workspace->plan ?? 'free';

        $plan = Plan::query()->where('key', $planKey)->first();

        if ($plan) {
            return $plan;
        }

        return Plan::query()->firstOrCreate(
            ['key' => 'free'],
            ['name' => 'Free', 'description' => 'Starter plan for small teams.'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function assertCan(Workspace $workspace, string $ability, array $context = []): void
    {
        $limits = $this->limits($workspace);

        match ($ability) {
            'invite_member' => $this->assertLimit(
                $workspace,
                $limits,
                'max_members',
                $this->usage($workspace)['members_count'],
                'Você atingiu o limite de membros do seu plano.',
            ),
            'create_board' => $this->assertLimit(
                $workspace,
                $limits,
                'max_boards',
                $this->usage($workspace)['boards_count'],
                'Você atingiu o limite de boards do seu plano.',
            ),
            'export' => $this->assertLimit(
                $workspace,
                $limits,
                'max_exports_per_month',
                $this->usage($workspace)['exports_count_month'],
                'Você atingiu o limite de exportações do mês.',
            ),
            'create_task' => $this->assertTaskLimit(
                $workspace,
                $limits,
                $context['board'] ?? null,
            ),
            'start_timer' => $this->assertTimerLimit(
                $workspace,
                $limits,
                $context['user'] ?? null,
            ),
            default => null,
        };
    }

    /**
     * @param  array<string, int|null>  $limits
     */
    private function assertLimit(
        Workspace $workspace,
        array $limits,
        string $limitKey,
        int $currentValue,
        string $message,
    ): void {
        $limitValue = $limits[$limitKey] ?? null;

        if ($limitValue === null) {
            return;
        }

        if ($currentValue < $limitValue) {
            return;
        }

        $this->throwLimit($workspace, $limitKey, $limitValue, $currentValue, $message);
    }

    /**
     * @param  array<string, int|null>  $limits
     */
    private function assertTaskLimit(
        Workspace $workspace,
        array $limits,
        ?TaskBoard $board,
    ): void {
        if (! $board) {
            return;
        }

        $limitValue = $limits['max_tasks_per_board'] ?? null;

        if ($limitValue === null) {
            return;
        }

        $currentValue = Task::query()
            ->whereHas('taskColumn', fn ($query) => $query->where('task_board_id', $board->id))
            ->count();

        if ($currentValue < $limitValue) {
            return;
        }

        $this->throwLimit(
            $workspace,
            'max_tasks_per_board',
            $limitValue,
            $currentValue,
            'Você atingiu o limite de tarefas por board.',
        );
    }

    /**
     * @param  array<string, int|null>  $limits
     */
    private function assertTimerLimit(
        Workspace $workspace,
        array $limits,
        ?User $user,
    ): void {
        if (! $user) {
            return;
        }

        $limitValue = $limits['max_active_timers_per_user'] ?? 1;

        if ($limitValue === null) {
            return;
        }

        $currentValue = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->whereHas('task.taskColumn.board', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->count();

        if ($currentValue < $limitValue) {
            return;
        }

        $this->throwLimit(
            $workspace,
            'max_active_timers_per_user',
            $limitValue,
            $currentValue,
            'Você atingiu o limite de timers ativos do seu plano.',
        );
    }

    private function throwLimit(
        Workspace $workspace,
        string $limitKey,
        int $limitValue,
        int $currentValue,
        string $message,
    ): void {
        $upgradeUrl = route('settings.plan', ['workspace' => $workspace->id]);

        throw new PlanLimitExceededException(
            $message,
            $limitKey,
            $limitValue,
            $currentValue,
            $upgradeUrl,
        );
    }
}
