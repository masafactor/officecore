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



}
