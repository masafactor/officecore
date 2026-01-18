<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('work_rules')->updateOrInsert(
        ['name' => '通常勤務'],
        [
            'work_start'  => '09:00:00',
            'work_end'    => '18:00:00',
            'break_start' => '12:00:00',
            'break_end'   => '13:00:00',
            'updated_at'  => now(),
            // created_at は insert のときだけ入れたいが updateOrInsert だと分岐しにくい
        ]
        );

    }
}
