<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmploymentType;

class EmploymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        EmploymentType::updateOrCreate(
            ['code' => 'regular'],
            ['name' => '正社員', 'overtime_policy' => 'scheduled_over']
        );

        EmploymentType::updateOrCreate(
            ['code' => 'part_time'],
            ['name' => '短時間バイト', 'overtime_policy' => 'legal_over']
        );
    }
}