<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DailyReportBulkPdfController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date');
        $userId = $request->input('user_id');
        $keyword = $request->input('keyword');

        $query = DailyReport::query()
            ->with('user');

        if (!empty($date)) {
            $query->whereDate('report_date', $date);
        }

        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        if (!empty($keyword)) {
            $query->where('content', 'like', '%' . $keyword . '%');
        }

        $dailyReports = $query
            ->orderBy('report_date', 'desc')
            ->orderBy('user_id')
            ->get();

        if ($dailyReports->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'PDF出力対象の日報がありません。');
        }

        if ($dailyReports->count() > 100) {
            return redirect()
                ->back()
                ->with('error', 'PDF出力件数が多すぎます。100件以内で絞り込んでください。');
        }

        
        $fileName = '日報一覧_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return Pdf::loadView('pdf.admin.daily-reports-bulk', [
            'dailyReports' => $dailyReports,
        ])->stream($fileName);
    }
}