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
        $database = DB::getDatabaseName();
        $indexExists = fn (string $name): bool => DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'task_columns')
            ->where('index_name', $name)
            ->exists();

        if (! $indexExists('task_columns_user_id_index')) {
            Schema::table('task_columns', function (Blueprint $table) {
                $table->index('user_id', 'task_columns_user_id_index');
            });
        }

        if ($indexExists('task_columns_user_id_slug_unique')) {
            Schema::table('task_columns', function (Blueprint $table) {
                $table->dropUnique('task_columns_user_id_slug_unique');
            });
        }

        $now = now();
        $users = DB::table('users')->pluck('id');

        foreach ($users as $userId) {
            $boardId = DB::table('task_boards')
                ->where('user_id', $userId)
                ->orderBy('sort_order')
                ->value('id');

            if (! $boardId) {
                $boardId = DB::table('task_boards')->insertGetId([
                    'user_id' => $userId,
                    'name' => 'Padrão',
                    'slug' => 'padrao',
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('task_columns')
                ->where('user_id', $userId)
                ->whereNull('task_board_id')
                ->update([
                    'task_board_id' => $boardId,
                    'updated_at' => $now,
                ]);
        }

        if (! $indexExists('task_columns_task_board_id_slug_unique')) {
            Schema::table('task_columns', function (Blueprint $table) {
                $table->unique(['task_board_id', 'slug'], 'task_columns_task_board_id_slug_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $database = DB::getDatabaseName();
        $indexExists = fn (string $name): bool => DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'task_columns')
            ->where('index_name', $name)
            ->exists();

        if ($indexExists('task_columns_task_board_id_slug_unique')) {
            Schema::table('task_columns', function (Blueprint $table) {
                $table->dropUnique('task_columns_task_board_id_slug_unique');
            });
        }

        $duplicates = DB::table('task_columns')
            ->select('user_id', 'slug', DB::raw('count(*) as total'))
            ->groupBy('user_id', 'slug')
            ->having('total', '>', 1)
            ->limit(1)
            ->exists();

        if (! $duplicates && ! $indexExists('task_columns_user_id_slug_unique')) {
            Schema::table('task_columns', function (Blueprint $table) {
                $table->unique(['user_id', 'slug'], 'task_columns_user_id_slug_unique');
            });
        }
    }
};
