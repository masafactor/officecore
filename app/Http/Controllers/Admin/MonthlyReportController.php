<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\WorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m')); // "2026-01"
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rule = WorkRule::where('name', '通常勤務')->first();

        $rows = Attendance::query()
            ->with(['user:id,name,email'])
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('user_id')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) use ($rule) {
                $user = $items->first()->user;

                $workedMinutesSum = 0;
                $workedDays = 0;
                $clockInDays = 0;
                $clockOutDays = 0;

                foreach ($items as $a) {
                    if ($a->clock_in) $clockInDays++;
                    if ($a->clock_out) $clockOutDays++;

                    if ($rule) {
                        $m = $a->workedMinutesForRule($rule);
                        if ($m !== null) {
                            $workedMinutesSum += $m;
                            $workedDays++;
                        }
                    }
                }

                $avg = $workedDays > 0 ? intdiv($workedMinutesSum, $workedDays) : null;

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'clock_in_days' => $clockInDays,
                    'clock_out_days' => $clockOutDays,
                    'worked_days' => $workedDays,
                    'worked_minutes_sum' => $workedMinutesSum,
                    'worked_minutes_avg' => $avg,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Admin/Reports/Monthly', [
            'filters' => [
                'month' => $month,
            ],
            'rows' => $rows,
        ]);
    }
}
