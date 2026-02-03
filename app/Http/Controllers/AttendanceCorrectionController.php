<?php

// app/Http/Controllers/AttendanceCorrectionController.php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttendanceCorrectionController extends Controller
{
    public function store(Request $request, Attendance $attendance)
    {
        // 自分の勤怠だけ申請できる（管理者は別）
        abort_unless($attendance->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'clock_in_at'  => ['nullable', 'date'],  // "YYYY-MM-DD HH:MM"
            'clock_out_at' => ['nullable', 'date'],
            'note'         => ['nullable', 'string'],
            'reason'       => ['nullable', 'string', 'max:500'],
        ]);

        // 同じ勤怠に pending があるなら二重申請防止（必要なら）
        $alreadyPending = AttendanceCorrection::query()
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('error', 'この勤怠には未処理の修正依頼があります。');
        }

        // 最低限：どれか一つは変更がある
        $hasAny = ($validated['clock_in_at'] ?? null) || ($validated['clock_out_at'] ?? null) || ($validated['note'] ?? null);
        if (!$hasAny) {
            return back()->with('error', '修正内容が空です。');
        }

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by'  => Auth::id(),
            'clock_in_at'   => $validated['clock_in_at'] ?? null,
            'clock_out_at'  => $validated['clock_out_at'] ?? null,
            'note'          => $validated['note'] ?? null,
            'reason'        => $validated['reason'] ?? null,
            'status'        => 'pending',
        ]);

        return back()->with('success', '修正依頼を送信しました。');
    }
}

