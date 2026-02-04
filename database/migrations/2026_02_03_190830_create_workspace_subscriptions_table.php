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
        Schema::create('workspace_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('plan_key');
            $table->string('status')->default('active');
            $table->timestamp('started_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
            $table->index('plan_key');
        });

        $now = now();

        $workspaces = DB::table('workspaces')->get(['id', 'plan']);

        $rows = $workspaces->map(function ($workspace) use ($now) {
            return [
                'workspace_id' => $workspace->id,
                'plan_key' => $workspace->plan ?: 'free',
                'status' => 'active',
                'started_at' => $now,
                'trial_ends_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if ($rows !== []) {
            DB::table('workspace_subscriptions')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_subscriptions');
    }
};
