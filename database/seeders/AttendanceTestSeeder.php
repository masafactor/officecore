<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceTestSeeder extends Seeder
{
    public function run(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $rows = [
            // id 3: 通常勤務
            [
                'user_id' => 3,
                'work_date' => $tomorrow,
                'clock_in' => Carbon::parse("{$tomorrow} 09:00:00"),
                'clock_out' => Carbon::parse("{$tomorrow} 18:00:00"),
                'note' => 'テストデータ: 通常勤務',
            ],

            // id 4: 少し残業
            [
                'user_id' => 4,
                'work_date' => $tomorrow,
                'clock_in' => Carbon::parse("{$tomorrow} 09:00:00"),
                'clock_out' => Carbon::parse("{$tomorrow} 19:30:00"),
                'note' => 'テストデータ: 残業あり',
            ],

            // id 5: 早出あり
            [
                'user_id' => 5,
                'work_date' => $tomorrow,
                'clock_in' => Carbon::parse("{$tomorrow} 08:30:00"),
                'clock_out' => Carbon::parse("{$tomorrow} 18:00:00"),
                'note' => 'テストデータ: 早出あり',
            ],

            // id 6: 深夜勤務っぽいデータ
            [
                'user_id' => 6,
                'work_date' => $tomorrow,
                'clock_in' => Carbon::parse("{$tomorrow} 20:00:00"),
                'clock_out' => Carbon::parse("{$tomorrow} 23:30:00"),
                'note' => 'テストデータ: 深夜あり',
            ],

            // id 7: 少し短め
            [
                'user_id' => 7,
                'work_date' => $tomorrow,
                'clock_in' => Carbon::parse("{$tomorrow} 10:00:00"),
                'clock_out' => Carbon::parse("{$tomorrow} 17:00:00"),
                'note' => 'テストデータ: 短時間勤務',
            ],
        ];

        foreach ($rows as $row) {
            Attendance::updateOrCreate(
                [
                    'user_id' => $row['user_id'],
                    'work_date' => $row['work_date'],
                ],
                [
                    'clock_in' => $row['clock_in'],
                    'clock_out' => $row['clock_out'],
                    'note' => $row['note'],
                ]
            );
        }
    }
}