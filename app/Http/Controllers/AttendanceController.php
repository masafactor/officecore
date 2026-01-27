<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class AttendanceController extends Controller
{
    public function clockIn(): RedirectResponse
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'work_date' => $today],
            ['clock_in' => null, 'clock_out' => null]
        );

        if ($attendance->clock_in) {
            return back()->with('error', 'すでに出勤打刻済みです。');
        }

        $attendance->update(['clock_in' => now()]);

        return back()->with('success', '出勤を打刻しました。');
    }

    public function clockOut(): RedirectResponse
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', '出勤打刻がありません。');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'すでに退勤打刻済みです。');
        }

        $attendance->update(['clock_out' => now()]);

        return back()->with('success', '退勤を打刻しました。');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // work_date の日付に時刻を合成して保存（timezoneはLaravel側設定に従う）
        if (array_key_exists('clock_in', $validated)) {
            $attendance->clock_in = $validated['clock_in']
                ? Carbon::parse($attendance->work_date->toDateString().' '.$validated['clock_in'])
                : null;
        }

        if (array_key_exists('clock_out', $validated)) {
            $attendance->clock_out = $validated['clock_out']
                ? Carbon::parse($attendance->work_date->toDateString().' '.$validated['clock_out'])
                : null;
        }

        $attendance->note = $validated['note'] ?? null;

        // 退勤が出勤より前なら弾く（簡易チェック）
        if ($attendance->clock_in && $attendance->clock_out && $attendance->clock_out->lt($attendance->clock_in)) {
            return back()->withErrors(['clock_out' => '退勤時刻は出勤時刻より後にしてください。']);
        }

        $attendance->save();

        return back()->with('success', '勤怠を更新しました。');
    }

}
