<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\UserWorkRule;
use App\Models\WorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $ruleMap = UserWorkRule::query()
        ->where('start_date', '<=', $date)
        ->where(function ($q) use ($date) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
        })
        ->get(['user_id', 'work_rule_id'])
        ->keyBy('user_id');

        $attendances = Attendance::query()
            ->with(['user:id,name,email'])
            ->where('work_date', $date)
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        $workRules = WorkRule::query()->get()->keyBy('id');
        $defaultRule = WorkRule::where('name', '通常勤務')->first();
        
        // Inertiaには必要項目だけ渡す（TSで扱いやすくする）
        $items = $attendances->through(function (Attendance $a) use ($ruleMap, $workRules, $defaultRule) {
            $ruleId = $ruleMap->get($a->user_id)?->work_rule_id ?? $defaultRule?->id;
            $rule = $ruleId ? $workRules->get($ruleId) : null;

            $workedMinutes = null;
            if ($rule) {
                $workedMinutes = $a->workedMinutesForRule($rule);
            }

            return [
                'id' => $a->id,
                'work_date' => $a->work_date->toDateString(),
                'clock_in'  => optional($a->clock_in)->format('H:i'),
                'clock_out' => optional($a->clock_out)->format('H:i'),
                'worked_minutes' => $workedMinutes,
                'note' => $a->note,
                'user' => [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'email' => $a->user->email,
                ],
                'updated_at' => $a->updated_at?->toISOString(),
                'overtime_minutes' => $rule ? $a->overtimeMinutesForRule($rule) : null,
                'night_minutes' => $a->nightMinutesForRule($rule),
                

            ];
        });


    return Inertia::render('Admin/Attendances/Index', [
            'filters' => [
                'date' => $date,
            ],
            'attendances' => [
                'data' => $items->values()->all(), // ✅ ここ
                'links' => $attendances->linkCollection(),
                'total' => $attendances->total(),
            ],
            
        ]);
    }



public function update(Request $request, Attendance $attendance)
{
    $validated = $request->validate([
        'clock_in_date'  => ['nullable', 'date_format:Y-m-d'],
        'clock_in'       => ['nullable', 'date_format:H:i'],
        'clock_out_date' => ['nullable', 'date_format:Y-m-d'],
        'clock_out'      => ['nullable', 'date_format:H:i'],
        'note'           => ['nullable', 'string', 'max:500'],
    ]);

    // 文字列を確実に null or string に寄せる
    $clockInDate  = $validated['clock_in_date']  ?? null;
    $clockInTime  = $validated['clock_in']       ?? null;
    $clockOutDate = $validated['clock_out_date'] ?? null;
    $clockOutTime = $validated['clock_out']      ?? null;

    // date 未指定なら attendance.work_date を採用
    $baseDate = $attendance->work_date?->toDateString() ?? now()->toDateString();
    $clockInDate  = $clockInDate  ?: $baseDate;
    $clockOutDate = $clockOutDate ?: $baseDate;

    // datetime に組み立て（片方だけ入ってるケースも許容）
    $clockIn  = $clockInTime
        ? Carbon::createFromFormat('Y-m-d H:i', "{$clockInDate} {$clockInTime}")
        : null;

    $clockOut = $clockOutTime
        ? Carbon::createFromFormat('Y-m-d H:i', "{$clockOutDate} {$clockOutTime}")
        : null;

    // 保険：両方あるのに退勤が出勤以前なら、翌日に補正（UIが失敗してても救う）
    if ($clockIn && $clockOut && $clockOut->lte($clockIn)) {
        $clockOut->addDay();
    }

    $attendance->clock_in  = $clockIn;
    $attendance->clock_out = $clockOut;
    $attendance->note      = $validated['note'] ?? null;

    $attendance->save();

    return redirect()
        ->back()
        ->with('success', '勤怠を更新しました。');
}


}
