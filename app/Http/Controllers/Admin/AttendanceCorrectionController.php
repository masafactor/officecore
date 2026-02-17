<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\WorkRule;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AttendanceCorrectionController extends Controller
{

public function index()
{
    $today = now()->toDateString();

    $corrections = AttendanceCorrection::query()
        ->with([
            'attendance.user',
            'requester',
        ])
        ->where('status', 'pending')
        ->latest()
        ->paginate(20)
        ->through(function ($c) use ($today) {
            $a = $c->attendance;

            // ✅ その勤怠に紐づく勤務ルールを取る（既存ロジック維持）
            $date = $a?->work_date?->toDateString() ?? $today;

            $rule = null;
            if ($a?->user) {
                $user = $a->user;

                $rule = WorkRule::query()
                    ->whereHas('userWorkRules', function ($q) use ($user, $date) {
                        $q->where('user_id', $user->id)
                          ->where('start_date', '<=', $date)
                          ->where(function ($q2) use ($date) {
                              $q2->whereNull('end_date')->orWhere('end_date', '>=', $date);
                          });
                    })
                    ->first();

                if (!$rule) {
                    $rule = WorkRule::where('name', '通常勤務')->first();
                }
            }

            // ✅ 分計算（モデルメソッド）
            $workedMinutes   = ($a && $rule) ? $a->workedMinutesForRule($rule) : null;
            $overtimeMinutes = ($a && $rule) ? $a->overtimeMinutesForRule($rule) : null;
            $totalMinutes    = ($a && $rule) ? $a->totalMinutesForRule($rule) : null;

            // ✅ 日跨ぎ判定：clock_out の日付が work_date と違うかで判定（これが重要）
            $isNextDay = false;
            if ($a?->clock_out && $a?->work_date) {
                $isNextDay = $a->clock_out->toDateString() !== $a->work_date->toDateString();
            }

            return [
                'id' => $c->id,
                'status' => $c->status,
                'reason' => $c->reason,
                'clock_in_at' => optional($c->clock_in_at)->toDateTimeString(),
                'clock_out_at' => optional($c->clock_out_at)->toDateTimeString(),
                'note' => $c->note,
                'created_at' => optional($c->created_at)->toDateTimeString(),

                'minutes' => [
                    'worked' => $workedMinutes,
                    'overtime' => $overtimeMinutes,
                    'total' => $totalMinutes,
                ],

                'attendance' => $a ? [
                    'id' => $a->id,
                    'work_date' => optional($a->work_date)->toDateString(),

                    // 表示用（HH:MM）※既存維持
                    'clock_in'  => optional($a->clock_in)->format('H:i'),
                    'clock_out' => optional($a->clock_out)->format('H:i'),

                    // ✅ 追加：日付付き（翌日問題の根本解決）
                    'clock_in_at'  => optional($a->clock_in)->format('Y-m-d H:i'),
                    'clock_out_at' => optional($a->clock_out)->format('Y-m-d H:i'),

                    // ✅ 修正：lte判定はやめて “日付差” にする
                    'is_next_day' => $isNextDay,
                ] : null,

                'user' => $a?->user ? [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'email' => $a->user->email,
                ] : null,

                'requester' => $c->requester ? [
                    'id' => $c->requester->id,
                    'name' => $c->requester->name,
                ] : null,

                'work_rule' => $rule ? [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'rounding_unit_minutes' => $rule->rounding_unit_minutes,
                ] : null,
            ];
        });

    return Inertia::render('Admin/AttendanceCorrections/Index', [
        'corrections' => $corrections,
    ]);
}






public function approve(Request $request, AttendanceCorrection $correction)
{
    $validated = $request->validate([
        'clock_in'  => ['nullable', 'date_format:H:i'],
        'clock_out' => ['nullable', 'date_format:H:i'],
        'clock_out_is_next_day' => ['nullable', 'boolean'], // ✅ 追加
        'note'      => ['nullable', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($validated, $correction) {
        $attendance = $correction->attendance;

        $workDate = $attendance->work_date instanceof \Carbon\Carbon
            ? $attendance->work_date->toDateString()
            : \Carbon\Carbon::parse($attendance->work_date)->toDateString();

        // ① 編集値があれば優先、なければ申請値
        $cin  = $validated['clock_in']  ?? optional($correction->clock_in_at)?->format('H:i');
        $cout = $validated['clock_out'] ?? optional($correction->clock_out_at)?->format('H:i');

        // ② datetime作成
        $clockInAt  = $cin  ? \Carbon\Carbon::parse("{$workDate} {$cin}:00") : null;
        $clockOutAt = $cout ? \Carbon\Carbon::parse("{$workDate} {$cout}:00") : null;

        // ③ 日跨ぎ補正（フラグ優先）
        $isNextDay = !empty($validated['clock_out_is_next_day']);
        if ($clockOutAt) {
            if ($isNextDay) {
                $clockOutAt->addDay();
            } elseif ($clockInAt && $clockOutAt->lte($clockInAt)) {
                $clockOutAt->addDay();
            }
        }

        // ④ attendance 更新（datetimeで保存）
        $attendance->update([
            'clock_in'  => $clockInAt,
            'clock_out' => $clockOutAt,
        ]);

        // ⑤ correction 更新（確定値として保持）
        $update = [
            'status'       => 'approved',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'clock_in_at'  => $clockInAt,
            'clock_out_at' => $clockOutAt,
        ];

        if (array_key_exists('note', $validated)) {
            $update['note'] = $validated['note'];
        }

        $correction->update($update);
    });

    return back()->with('success', '承認して勤怠を更新しました。');
}




    public function reject(Request $request, AttendanceCorrection $correction)
    {
        if ($correction->status !== 'pending') {
            return back()->with('error', 'この申請は既に処理済みです。');
        }

        $correction->status = 'rejected';
        $correction->reviewed_by = $request->user()->id;
        $correction->reviewed_at = now();
        $correction->save();

        return back()->with('success', '申請を却下しました。');
    }


}





