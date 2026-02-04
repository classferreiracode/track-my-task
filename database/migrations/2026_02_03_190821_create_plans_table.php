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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('plans')->insert([
            [
                'key' => 'free',
                'name' => 'Free',
                'description' => 'Starter plan for small teams.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'description' => 'Plan for growing teams.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'business',
                'name' => 'Business',
                'description' => 'Advanced plan for larger operations.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
