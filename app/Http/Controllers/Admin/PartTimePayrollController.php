<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class PartTimePayrollController extends Controller
{
    public function index()
    {
        $month = request('month', now()->format('Y-m'));

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $users = User::query()
            ->whereHas('userEmployments.employmentType', function ($query) {
                $query->where('code', 'part_time');
            })
            ->with([
                'userEmployments' => function ($query) use ($end) {
                    $query->with([
                        'employmentType:id,code,name',
                        'wageTable:id,employment_type_id,code,name,hourly_wage',
                    ])
                    ->where('start_date', '<=', $end->toDateString())
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

            $hourlyWage = $employment?->wageTable?->hourly_wage ?? 0;
            $wageTableName = $employment?->wageTable?->name;

            $attendances = Attendance::query()
                ->where('user_id', $user->id)
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            $workedMinutes = 0;
            $overtimeMinutes = 0;
            $lateNightMinutes = 0;

            foreach ($attendances as $attendance) {
                // ここは既存メソッド名に合わせて必要なら調整
                $workedMinutes += method_exists($attendance, 'workedMinutes')
                    ? (int) $attendance->workedMinutes()
                    : 0;

                $overtimeMinutes += method_exists($attendance, 'overtimeMinutes')
                    ? (int) $attendance->overtimeMinutes()
                    : 0;

                $lateNightMinutes += method_exists($attendance, 'lateNightMinutes')
                    ? (int) $attendance->lateNightMinutes()
                    : 0;
            }

            $workedHours = round($workedMinutes / 60, 2);
            $overtimeHours = round($overtimeMinutes / 60, 2);
            $lateNightHours = round($lateNightMinutes / 60, 2);

            $baseAmount = floor(($workedMinutes / 60) * $hourlyWage);
            $overtimePremium = floor(($overtimeMinutes / 60) * $hourlyWage * 0.25);
            $lateNightPremium = floor(($lateNightMinutes / 60) * $hourlyWage * 0.25);

            $estimatedAmount = $baseAmount + $overtimePremium + $lateNightPremium;

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'wage_table_name' => $wageTableName,
                'hourly_wage' => $hourlyWage,
                'worked_hours' => $workedHours,
                'overtime_hours' => $overtimeHours,
                'late_night_hours' => $lateNightHours,
                'base_amount' => $baseAmount,
                'overtime_premium' => $overtimePremium,
                'late_night_premium' => $lateNightPremium,
                'estimated_amount' => $estimatedAmount,
            ];
        });

        return Inertia::render('Admin/Payrolls/PartTime/Index', [
            'month' => $month,
            'rows' => $rows,
        ]);
    }
}