<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

class AttendanceCorrectionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command?->warn('ユーザーがいません');
            return;
        }

        // 勤怠が無いと申請を紐付けできないので、なければ1件作る
        $attendance = Attendance::first();
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => now()->toDateString(),
                'clock_in' => now()->setTime(9, 0),
                'clock_out' => now()->setTime(18, 0),
                'note' => null,
            ]);
        }

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by'  => $user->id,
            'status'        => 'pending',
            'reason'        => '打刻を忘れました（Seeder）',
            'clock_in_at'   => now()->startOfDay()->setTime(9, 30),
            'clock_out_at'  => now()->startOfDay()->setTime(18, 30),
            'note'          => 'テスト修正申請',
        ]);

        $this->command?->info('AttendanceCorrection を1件作成しました');
    }
}
