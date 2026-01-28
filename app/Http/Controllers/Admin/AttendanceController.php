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
        'clock_in' => ['nullable', 'date_format:H:i'],
        'clock_out' => ['nullable', 'date_format:H:i'],
        'note' => ['nullable', 'string', 'max:500'],
    ]);

    $tz = config('app.timezone', 'Asia/Tokyo');
    $date = $attendance->work_date->toDateString();

    $attendance->clock_in = !empty($validated['clock_in'])
        ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$validated['clock_in']}", $tz)
        : null;

    $attendance->clock_out = !empty($validated['clock_out'])
        ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$validated['clock_out']}", $tz)
        : null;

    $attendance->note = $validated['note'] ?? null;

    if ($attendance->clock_in && $attendance->clock_out && $attendance->clock_out->lt($attendance->clock_in)) {
        return back()->withErrors(['clock_out' => '退勤時刻は出勤時刻より後にしてください。']);
    }

    $attendance->save();

    return back()->with('success', '勤怠を更新しました。');
}

}
