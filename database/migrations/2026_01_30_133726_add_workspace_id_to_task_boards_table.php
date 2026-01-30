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
        Schema::table('task_boards', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
        });

        $now = now();
        $userIds = DB::table('task_boards')->select('user_id')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $workspaceId = DB::table('workspaces')
                ->where('owner_user_id', $userId)
                ->value('id');

            if (! $workspaceId) {
                $workspaceId = DB::table('workspaces')->insertGetId([
                    'owner_user_id' => $userId,
                    'name' => 'Padrão',
                    'slug' => 'padrao',
                    'plan' => 'free',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('workspace_memberships')->insert([
                    'workspace_id' => $workspaceId,
                    'user_id' => $userId,
                    'role' => 'owner',
                    'weekly_capacity_minutes' => null,
                    'is_active' => true,
                    'joined_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('task_boards')
                ->where('user_id', $userId)
                ->update([
                    'workspace_id' => $workspaceId,
                ]);
        }

        Schema::table('task_boards', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_boards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
