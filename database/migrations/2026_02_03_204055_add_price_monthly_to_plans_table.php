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
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('price_monthly')->default(0)->after('description');
        });

        $pricing = config('plan.pricing', []);

        foreach ($pricing as $key => $price) {
            DB::table('plans')
                ->where('key', $key)
                ->update(['price_monthly' => $price]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('price_monthly');
        });
    }
};
