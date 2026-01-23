<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\UserWorkRule;
use App\Models\WorkRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
                'clock_in' => optional($a->clock_in)->toISOString(),
                'clock_out' => optional($a->clock_out)->toISOString(),
                'worked_minutes' => $workedMinutes,
                'note' => $a->note,
                'user' => [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'email' => $a->user->email,
                ],
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
}
