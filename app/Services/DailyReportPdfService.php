<?php

namespace App\Services;

use App\Models\DailyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DailyReportPdfService
{
    public function streamAdminDetail(DailyReport $dailyReport)
    {
        $workDate = $dailyReport->work_date
            ? Carbon::parse($dailyReport->work_date)->format('Ymd')
            : $dailyReport->id;


        $workDate = Carbon::parse($dailyReport->report_date)->format('Y-m-d');
            $userName = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $dailyReport->user->name);

            $fileName = sprintf(
                '%s_日報_%s.pdf',
                $userName,
                $workDate
        );

        return Pdf::loadView('pdf.admin.daily-report-show', [
            'dailyReport' => $dailyReport,
        ])->stream($fileName);
    }
}