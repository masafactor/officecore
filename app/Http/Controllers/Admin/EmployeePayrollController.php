<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        [$rows, $summary] = $this->buildRows($month);

        return Inertia::render('Admin/Payrolls/Employees/Index', [
            'month' => $month,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $month = $request->input('month', now()->format('Y-m'));

        [$rows] = $this->buildRows($month);

        $fileName = "employee-payroll-{$month}.csv";

        return response()->streamDownload(function () use ($rows, $month) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                '対象月',
                '社員名',
                '雇用形態',
                '固定給',
                '所定労働時間',
                '残業時間',
                '時間単価',
                '残業代',
                '適用開始日',
                '理由',
                '支給見込額',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $month,
                    $row['user_name'],
                    $row['employment_type_name'],
                    $row['base_salary'],
                    $row['scheduled_hours'],
                    $row['overtime_hours'],
                    $row['hourly_rate'],
                    $row['overtime_amount'],
                    $row['salary_start_date'],
                    $row['salary_reason'],
                    $row['estimated_amount'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildRows(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $users = User::query()
            ->whereHas('userEmployments', function ($query) use ($end) {
                $query->where('start_date', '<=', $end->toDateString())
                    ->where(function ($q) use ($end) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $end->toDateString());
                    })
                    ->whereHas('employmentType', function ($q) {
                        $q->whereIn('code', ['regular', 'full_time']);
                    });
            })
            ->with([
                'userEmployments' => function ($query) use ($end) {
                    $query->with('employmentType:id,code,name,overtime_policy')
                        ->where('start_date', '<=', $end->toDateString())
                        ->where(function ($q) use ($end) {
                            $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $end->toDateString());
                        })
                        ->orderByDesc('start_date');
                },
                'employeeSalaryHistories' => function ($query) use ($end) {
                    $query->where('start_date', '<=', $end->toDateString())
                        ->where(function ($q) use ($end) {
                            $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $end->toDateString());
                        })
                        ->orderByDesc('start_date');
                },
            ])
            ->orderBy('id')
            ->get();

        $rows = $users->map(function ($user) use ($start, $end) {
            $employment = $user->userEmployments->first();
            $salary = $user->employeeSalaryHistories->first();

            $baseSalary = $salary?->base_salary ?? 0;
            $policy = $employment?->employmentType?->overtime_policy ?? 'scheduled_over';

            $attendances = Attendance::query()
                ->where('user_id', $user->id)
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            $scheduledMinutes = 0;
            $overtimeMinutes = 0;

            foreach ($attendances as $attendance) {
                $date = $attendance->work_date->toDateString();

                $workRule = WorkRule::query()
                    ->whereHas('userWorkRules', function ($q) use ($user, $date) {
                        $q->where('user_id', $user->id)
                            ->where('start_date', '<=', $date)
                            ->where(function ($q2) use ($date) {
                                $q2->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $date);
                            });
                    })
                    ->first();

                if (! $workRule) {
                    $workRule = WorkRule::where('name', '通常勤務')->first();
                }

                if (! $workRule) {
                    continue;
                }

                $scheduledMinutes += $attendance->scheduledMinutesForRule($workRule) ?? 0;
                $overtimeMinutes += $attendance->overtimeMinutesWithPolicy($workRule, $policy) ?? 0;
            }

            $scheduledHours = round($scheduledMinutes / 60, 2);
            $overtimeHours = round($overtimeMinutes / 60, 2);

            $hourlyRate = $scheduledMinutes > 0
                ? floor($baseSalary / ($scheduledMinutes / 60))
                : 0;

            $overtimeAmount = (int) floor(($overtimeMinutes / 60) * $hourlyRate * 1.25);
            $estimatedAmount = $baseSalary + $overtimeAmount;

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'employment_type_name' => $employment?->employmentType?->name,
                'base_salary' => $baseSalary,
                'scheduled_hours' => $scheduledHours,
                'overtime_hours' => $overtimeHours,
                'hourly_rate' => $hourlyRate,
                'overtime_amount' => $overtimeAmount,
                'salary_start_date' => $salary?->start_date?->toDateString(),
                'salary_end_date' => $salary?->end_date?->toDateString(),
                'salary_reason' => $salary?->reason,
                'estimated_amount' => $estimatedAmount,
            ];
        })->values();

        $summary = [
            'user_count' => $rows->count(),
            'base_salary_total' => (int) $rows->sum('base_salary'),
            'overtime_amount_total' => (int) $rows->sum('overtime_amount'),
            'estimated_amount_total' => (int) $rows->sum('estimated_amount'),
        ];

        return [$rows, $summary];
    }
}