<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Services\DailyReportPdfService;
use Illuminate\Http\Request;

class DailyReportPdfController extends Controller
{
    public function show(DailyReport $dailyReport, DailyReportPdfService $dailyReportPdfService)
    {
        $dailyReport->load('user');

        return $dailyReportPdfService->streamAdminDetail($dailyReport);
    }
}
