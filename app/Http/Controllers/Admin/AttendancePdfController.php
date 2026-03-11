<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceClosing;
use App\Models\User;
use App\Services\MonthlyAttendancePdfService;
use App\Services\MonthlyAttendanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendancePdfController extends Controller
{
    public function __construct(
        private MonthlyAttendanceReportService $reportService,
        private MonthlyAttendancePdfService $pdfService,
    ) {
    }

    public function downloadMonthlyRequestDetail(Request $request, int $userId, int $year, int $month)
    {
        $user = User::findOrFail($userId);

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = (clone $from)->endOfMonth();

        // 月次締め・申請情報
        $closing = AttendanceClosing::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        // 既存サービスから月次レポート取得
        $report = $this->reportService->buildForUser($user, $from, $to);

        // サービスの返却形式に応じて調整
        $summary = data_get($report, 'summary', []);
        $attendances = data_get($report, 'attendances', data_get($report, 'rows', []));

        $requestStatus = $closing?->status ?? '下書き';
        $submittedAt = $closing?->submitted_at;
        $approvedAt = $closing?->approved_at;
        $remarks = $closing?->remarks;

        // 承認者名
        $approverName = null;
        if ($closing?->approved_by) {
            $approverName = User::query()
                ->where('id', $closing->approved_by)
                ->value('name');
        }

     $pdf = Pdf::loadView('pdf.monthly-request-detail', [
            'user' => $user,
            'userName' => $user->name,
            'year' => $year,
            'month' => $month,
            'requestStatus' => $requestStatus,
            'submittedAt' => $submittedAt,
            'approvedAt' => $approvedAt,
            'approverName' => $approverName,
            'summary' => $summary,
            'attendances' => $attendances,
            'remarks' => $remarks,
        ])->setPaper('a4', 'portrait');

        
        $userName = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $user->name);
        $fileName = sprintf('月次申請詳細_%s_%04d年%02d月.pdf', $userName, $year, $month);

        return $pdf->download($fileName);
    }
}