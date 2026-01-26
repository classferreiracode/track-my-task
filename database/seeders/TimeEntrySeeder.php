<?php

namespace Database\Seeders;

use App\Models\TimeEntry;
use Illuminate\Database\Seeder;

class TimeEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TimeEntry::factory()->count(12)->create();
    }
}
