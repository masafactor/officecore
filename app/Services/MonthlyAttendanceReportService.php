<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Models\UserWorkRule;
use Carbon\Carbon;

class MonthlyAttendanceReportService
{
public function buildForUser(User $user, Carbon $start, Carbon $end): array
{
    $attendances = Attendance::query()
        ->where('user_id', $user->id)
        ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
        ->orderBy('work_date')
        ->get();

    $rows = $attendances->map(function (Attendance $attendance) use ($user) {
        $ruleAssignment = UserWorkRule::query()
            ->with('workRule')
            ->where('user_id', $user->id)
            ->where('start_date', '<=', $attendance->work_date->toDateString())
            ->where(function ($q) use ($attendance) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $attendance->work_date->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();

        $rule = $ruleAssignment?->workRule;

        $scheduled = $rule ? ($attendance->scheduledMinutesForRule($rule) ?? 0) : 0;
        $total = $rule ? ($attendance->totalMinutesForRule($rule) ?? 0) : 0;
        $night = $rule ? ($attendance->nightMinutesForRule($rule) ?? 0) : 0;

        $policy = 'scheduled_over';
        $breakdown = $rule
            ? ($attendance->overtimeBreakdownMinutesWithPolicy($rule, $policy) ?? ['in' => 0, 'out' => 0])
            : ['in' => 0, 'out' => 0];

        return [
            'id' => $attendance->id,
            'work_date' => $attendance->work_date?->format('Y-m-d'),
            'clock_in' => $attendance->clock_in?->format('H:i'),
            'clock_out' => $attendance->clock_out?->format('H:i'),
            'note' => $attendance->note,
            'is_late' => (bool) $attendance->is_late,
            'is_early_leave' => (bool) $attendance->is_early_leave,
            'minutes' => [
                'scheduled_work_minutes' => $scheduled,
                'total_work_minutes' => $total,
                'overtime' => [
                    'in' => $breakdown['in'] ?? 0,
                    'out' => $breakdown['out'] ?? 0,
                ],
                'night' => $night,
            ],
        ];
    });

    $summary = [
        'working_days' => $rows->count(),
        'late_count' => $rows->where('is_late', true)->count(),
        'early_leave_count' => $rows->where('is_early_leave', true)->count(),
        'scheduled_minutes' => $rows->sum(fn ($row) => $row['minutes']['scheduled_work_minutes']),
        'actual_work_minutes' => $rows->sum(fn ($row) => $row['minutes']['total_work_minutes']),
        'overtime_in_minutes' => $rows->sum(fn ($row) => $row['minutes']['overtime']['in']),
        'overtime_out_minutes' => $rows->sum(fn ($row) => $row['minutes']['overtime']['out']),
        'night_minutes' => $rows->sum(fn ($row) => $row['minutes']['night']),
    ];

    return [
        'rows' => $rows,
        'summary' => $summary,
    ];
}
}