<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagementPlanUpdateRequest;
use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->with('limits')
            ->orderByRaw("field(`key`, 'free', 'pro', 'business')")
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'key' => $plan->key,
                'name' => $plan->name,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'limits' => $plan->limits
                    ->mapWithKeys(fn (PlanLimit $limit) => [$limit->limit_key => $limit->limit_value])
                    ->all(),
            ])
            ->values();

        return Inertia::render('management/Plans', [
            'plans' => $plans,
            'limitKeys' => config('plan.limit_keys', []),
        ]);
    }

    public function update(ManagementPlanUpdateRequest $request, Plan $plan): RedirectResponse
    {
        $data = $request->validated();

        $plan->update([
            'price_monthly' => $data['price_monthly'],
        ]);

        $limits = $data['limits'] ?? [];

        foreach (config('plan.limit_keys', []) as $limitKey) {
            $value = $limits[$limitKey] ?? null;

            PlanLimit::query()->updateOrCreate(
                ['plan_id' => $plan->id, 'limit_key' => $limitKey],
                ['limit_value' => $value],
            );
        }

        return back()->with('success', 'Plano atualizado.');
    }
}
