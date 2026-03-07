<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceClosing;
use App\Models\User;
use App\Models\UserWorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceClosingController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->input('year', now()->year));
        $month = (int) ($request->input('month', now()->subMonthNoOverflow()->month));

        

        $closings = AttendanceClosing::query()
            ->with(['user:id,name', 'approver:id,name'])
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('status')
            ->orderBy('user_id')
            ->get()
            ->map(function ($closing) {
                return [
                    'id' => $closing->id,
                    'user_id' => $closing->user_id,
                    'user_name' => $closing->user?->name ?? '不明',
                    'year' => $closing->year,
                    'month' => $closing->month,
                    'status' => $closing->status,
                    'submitted_at' => $closing->submitted_at?->format('Y-m-d H:i:s'),
                    'approved_at' => $closing->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $closing->approved_by,
                    'approved_by_name' => $closing->approver?->name,
                ];
            });

        return Inertia::render('Admin/AttendanceClosings/Index', [
            'filters' => [
                'year' => $year,
                'month' => $month,
            ],
            'closings' => $closings,
        ]);
    }


    public function show(Request $request, User $user, int $year, int $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $closing = AttendanceClosing::query()
            ->with(['user:id,name', 'approver:id,name'])
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

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

            // policy は今の運用に合わせて
            // 例: work_rule に overtime_type があるならそれを使う
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
                    'overtime_in' => $breakdown['in'] ?? 0,
                    'overtime_out' => $breakdown['out'] ?? 0,
                    'night' => $night,
                ],
            ];
        });

        $summary = [
            'attendance_days' => $rows->count(),
            'late_count' => $rows->where('is_late', true)->count(),
            'early_leave_count' => $rows->where('is_early_leave', true)->count(),
            'scheduled_work_minutes' => $rows->sum(fn ($row) => $row['minutes']['scheduled_work_minutes']),
            'total_work_minutes' => $rows->sum(fn ($row) => $row['minutes']['total_work_minutes']),
            'overtime_in_minutes' => $rows->sum(fn ($row) => $row['minutes']['overtime_in']),
            'overtime_out_minutes' => $rows->sum(fn ($row) => $row['minutes']['overtime_out']),
            'night_minutes' => $rows->sum(fn ($row) => $row['minutes']['night']),
        ];

        return Inertia::render('Admin/AttendanceClosings/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'year' => $year,
            'month' => $month,
            'closing' => $closing ? [
                'id' => $closing->id,
                'status' => $closing->status,
                'submitted_at' => $closing->submitted_at?->format('Y-m-d H:i:s'),
                'approved_at' => $closing->approved_at?->format('Y-m-d H:i:s'),
                'approved_by' => $closing->approved_by,
                'approved_by_name' => $closing->approver?->name,
            ] : null,
            'summary' => $summary,
            'attendances' => $rows,
        ]);
    }
}