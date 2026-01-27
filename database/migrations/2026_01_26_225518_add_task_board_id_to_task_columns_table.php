<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_columns', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('task_columns', function (Blueprint $table) {
            $table->foreignId('task_board_id')
                ->nullable()
                ->after('user_id')
                ->constrained('task_boards')
                ->cascadeOnDelete();

            $table->dropUnique(['user_id', 'slug']);
            $table->unique(['task_board_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_columns', function (Blueprint $table) {
            $table->dropUnique(['task_board_id', 'slug']);
            $table->unique(['user_id', 'slug']);
            $table->dropConstrainedForeignId('task_board_id');
            $table->dropIndex(['user_id']);
        });
    }
};
