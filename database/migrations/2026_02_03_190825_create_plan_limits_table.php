<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('limit_key');
            $table->unsignedInteger('limit_value')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'limit_key']);
        });

        $planIds = DB::table('plans')
            ->whereIn('key', ['free', 'pro', 'business'])
            ->pluck('id', 'key');

        $limits = [
            'free' => [
                'max_members' => 3,
                'max_boards' => 3,
                'max_tasks_per_board' => null,
                'max_exports_per_month' => 1,
                'max_active_timers_per_user' => 1,
            ],
            'pro' => [
                'max_members' => 10,
                'max_boards' => 10,
                'max_tasks_per_board' => 200,
                'max_exports_per_month' => 50,
                'max_active_timers_per_user' => 3,
            ],
            'business' => [
                'max_members' => 50,
                'max_boards' => 50,
                'max_tasks_per_board' => 1000,
                'max_exports_per_month' => 500,
                'max_active_timers_per_user' => 10,
            ],
        ];

        $rows = [];
        foreach ($limits as $planKey => $planLimits) {
            $planId = $planIds[$planKey] ?? null;
            if (! $planId) {
                continue;
            }

            foreach ($planLimits as $limitKey => $limitValue) {
                $rows[] = [
                    'plan_id' => $planId,
                    'limit_key' => $limitKey,
                    'limit_value' => $limitValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('plan_limits')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
