<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AttendanceCorrectionController extends Controller
{
    public function index()
    {
        $corrections = AttendanceCorrection::query()
            ->with(['attendance.user', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20)
            ->through(fn ($c) => [
                'id' => $c->id,
                'status' => $c->status,
                'reason' => $c->reason,
                'clock_in_at' => optional($c->clock_in_at)->toDateTimeString(),
                'clock_out_at' => optional($c->clock_out_at)->toDateTimeString(),
                'note' => $c->note,
                'created_at' => optional($c->created_at)->toDateTimeString(),

                'attendance' => $c->attendance ? [
                    'id' => $c->attendance->id,
                    'work_date' => optional($c->attendance->work_date)->toDateString(),
                    'clock_in' => $c->attendance->clock_in ? substr((string) $c->attendance->clock_in, 0, 5) : null,
                    'clock_out' => $c->attendance->clock_out ? substr((string) $c->attendance->clock_out, 0, 5) : null,
                ] : null,

                'user' => $c->attendance?->user ? [
                    'id' => $c->attendance->user->id,
                    'name' => $c->attendance->user->name,
                    'email' => $c->attendance->user->email,
                ] : null,

                'requester' => $c->requester ? [
                    'id' => $c->requester->id,
                    'name' => $c->requester->name,
                ] : null,
            ]);

        return Inertia::render('Admin/AttendanceCorrections/Index', [
            'corrections' => $corrections,
        ]);
    }




    public function approve(Request $request, AttendanceCorrection $correction)
    {
        $validated = $request->validate([
            'clock_in'  => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'note'      => ['nullable', 'string', 'max:500'],
        ]);

        

        DB::transaction(function () use ($validated, $correction) {

            $attendance = $correction->attendance;

            // work_date が string でも落ちないように保険
            $workDate = $attendance->work_date instanceof \Carbon\Carbon
                ? $attendance->work_date->toDateString()
                : \Carbon\Carbon::parse($attendance->work_date)->toDateString();

            // ① 画面で編集した値が来てたらそれを優先、なければ申請値を使う
            $cin  = $validated['clock_in']  ?? optional($correction->clock_in_at)?->format('H:i');
            $cout = $validated['clock_out'] ?? optional($correction->clock_out_at)?->format('H:i');

            // ② correction 側の確定値（datetime）を作る（必要なら保存）
            $clockInAt  = $cin  ? \Carbon\Carbon::parse("{$workDate} {$cin}:00") : null;
            $clockOutAt = $cout ? \Carbon\Carbon::parse("{$workDate} {$cout}:00") : null;

            // ③ 日跨ぎ補正
            if ($clockInAt && $clockOutAt && $clockOutAt->lt($clockInAt)) {
                $clockOutAt->addDay();
            }

            // ④ attendance 更新（※ clock_in / clock_out が "HH:MM" の文字列カラム想定）
            $attendance->update([
                'clock_in'  => $cin,
                'clock_out' => $cout,
            ]);

            // ⑤ correction 更新（noteは送られた時だけ更新）
            $update = [
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                // 確定値を保持したいなら入れる（表示にも効く）
                'clock_in_at'  => $clockInAt,
                'clock_out_at' => $clockOutAt,
            ];

            if (array_key_exists('note', $validated)) {
                $update['note'] = $validated['note']; // null/空文字もそのまま反映
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





