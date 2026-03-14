<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class WeeklyDailyReportPdfService
{
    public function downloadForUser(User $user, Carbon $start, Carbon $end, string $fileName): Response
    {
        $reports = DailyReport::query()
            ->where('user_id', $user->id)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('report_date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->report_date)->toDateString());

        $pages = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $report = $reports->get($key);

            $pages->push([
                'date' => $date->copy(),
                'content' => $report?->content ?? '',
            ]);
        }

        $pdf = Pdf::loadView('pdf.weekly-daily-reports', [
            'user' => $user,
            'start' => $start,
            'end' => $end,
            'pages' => $pages,
        ]);

        return $pdf->download($fileName);
    }
}