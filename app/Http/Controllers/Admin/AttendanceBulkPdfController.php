<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MonthlyAttendancePdfService;
use App\Services\MonthlyAttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceBulkPdfController extends Controller
{
    public function __construct(
        private MonthlyAttendanceReportService $reportService,
        private MonthlyAttendancePdfService $pdfService,
    ) {
    }

    public function download(Request $request, int $year, int $month)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $users = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->orderBy('id')
            ->get();

        $reports = [];

        foreach ($users as $user) {
            $baseReport = $this->reportService->buildForUser($user, $start, $end);

            $reports[] = [
                'user' => $user,
                'year' => $year,
                'month' => $month,
                'start' => $start,
                'end' => $end,
                'rows' => $baseReport['rows'],
                'summary' => $baseReport['summary'],
            ];
        }

        $fileName = sprintf('monthly-attendance-bulk-%04d-%02d.pdf', $year, $month);

        return $this->pdfService->downloadBulk($reports, $fileName);
    }
}