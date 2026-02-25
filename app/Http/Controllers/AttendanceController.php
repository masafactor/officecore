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
        $rule = auth()->user()->currentWorkRule();
        if (!$rule) return back()->with('error', '勤務ルールが未設定です（管理者に連絡してください）。');
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

        // 1) 「未退勤」(clock_inあり & clock_outなし) を最新から探す
        $attendance = Attendance::query()
            ->where('user_id', $userId)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->orderByDesc('work_date')
            ->first();

        if (!$attendance) {
            return back()->with('error', '未退勤の勤怠がありません。');
        }

        // 2) ガード：すでに退勤済み（基本ここには来ないが保険）
        if ($attendance->clock_out) {
            return back()->with('error', 'すでに退勤打刻済みです。');
        }

        // 3) 退勤打刻
        $attendance->update(['clock_out' => now()]);

        $user = $attendance->user;
        $rule = $user?->currentWorkRule($attendance->work_date);

        if ($rule) {
            $attendance->updateLateEarlyFlags($rule);
            $attendance->save();
        }
        

        return back()->with('success', '退勤を打刻しました。');
    }




}
