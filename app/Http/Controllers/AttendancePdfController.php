<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\WorkRule;
use App\Services\MonthlyAttendancePdfService;
use App\Services\MonthlyAttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendancePdfController extends Controller
{
    public function __construct(
        private MonthlyAttendanceReportService $reportService,
        private MonthlyAttendancePdfService $pdfService,
    ) {
    }

    public function downloadMyMonthly(Request $request, int $year, int $month)
    {
        $user = $request->user();

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to   = (clone $from)->endOfMonth();

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date', 'desc')
            ->get()
            ->map(function (Attendance $a) use ($user) {
                $date = $a->work_date?->toDateString() ?? now()->toDateString();

                // 勤務履歴画面と同じロジック
                $rule = $user->currentWorkRule(Carbon::parse($date));
                if (!$rule) {
                    $rule = WorkRule::where('name', '通常勤務')->first();
                }

                $employment = method_exists($user, 'employmentOn')
                    ? $user->employmentOn(Carbon::parse($date))
                    : null;

                $policy = $employment?->overtime_policy ?? 'scheduled_over';

                $scheduled_work_minutes = ($a->clock_in && $a->clock_out && $rule)
                    ? $a->workedWithinScheduleMinutesForRule($rule)
                    : null;

                $breakdown = ($a->clock_in && $a->clock_out && $rule && method_exists($a, 'overtimeBreakdownMinutes'))
                    ? $a->overtimeBreakdownMinutes($rule, $policy)
                    : null;

                $nightMinutes = ($a->clock_in && $a->clock_out && $rule)
                    ? $a->nightMinutesForRule($rule)
                    : null;

                $total_work_minutes = ($a->clock_in && $a->clock_out && $rule)
                    ? $a->totalMinutesForRule($rule)
                    : null;

                return [
                    'id' => $a->id,
                    'work_date' => $date,
                    'clock_in'  => optional($a->clock_in)->format('H:i'),
                    'clock_out' => optional($a->clock_out)->format('H:i'),
                    'note' => $a->note,
                    'updated_at' => optional($a->updated_at)->toDateTimeString(),
                    'is_late' => (bool)($a->is_late ?? false),
                    'is_early_leave' => (bool)($a->is_early_leave ?? false),

                    'minutes' => [
                        'scheduled_work_minutes' => $scheduled_work_minutes ?? 0,
                        'total_work_minutes' => $total_work_minutes ?? 0,
                        'overtime' => [
                            'in'  => $breakdown['in']  ?? 0,
                            'out' => $breakdown['out'] ?? 0,
                        ],
                        'night' => $nightMinutes ?? 0,
                    ],
                ];
            })
            ->values();

        $data = [
            'user' => $user,
            'year' => $year,
            'month' => $month,
            'attendances' => $attendances,
        ];

    $fileName = "{$user->name}_{$year}_{$month}_勤怠月報.pdf";

        // まずはHTMLで中身確認
        // return view('pdf.monthly-attendance', $data);

        // PDFに戻すときはこっち
        return $this->pdfService->downloadSingle($data,$fileName);
    }
}