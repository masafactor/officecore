<?php

namespace Database\Seeders;

use App\Models\EmploymentType;
use App\Models\WageTable;
use Illuminate\Database\Seeder;

class WageTableSeeder extends Seeder
{
    public function run(): void
    {
        $partTime = EmploymentType::where('code', 'part_time')->first();

        if (! $partTime) {
            return;
        }

        $rows = [
            ['code' => 'pt_1_2026', 'name' => 'アルバイト1等級', 'hourly_wage' => 1114],
            ['code' => 'pt_2_2026', 'name' => 'アルバイト2等級', 'hourly_wage' => 1150],
            ['code' => 'pt_3_2026', 'name' => 'アルバイト3等級', 'hourly_wage' => 1200],
            ['code' => 'pt_4_2026', 'name' => 'アルバイト4等級', 'hourly_wage' => 1250],
            ['code' => 'pt_5_2026', 'name' => 'アルバイト5等級', 'hourly_wage' => 1300],
        ];

        foreach ($rows as $row) {
            WageTable::updateOrCreate(
                ['code' => $row['code']],
                [
                    'employment_type_id' => $partTime->id,
                    'name' => $row['name'],
                    'hourly_wage' => $row['hourly_wage'],
                    'start_date' => '2026-04-01',
                    'end_date' => null,
                ]
            );
        }
    }
}