<?php

namespace App\Http\Controllers;

use App\Services\WeeklyDailyReportPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DailyReportPdfController extends Controller
{
    public function __construct(
        private WeeklyDailyReportPdfService $pdfService,
    ) {
    }

    public function downloadWeekly(Request $request)
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date'],
        ]);

        $user = $request->user();

        $start = Carbon::parse($validated['week_start'])->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'week_start' => '週の指定が不正です。',
            ]);
        }


        $safeUserName = preg_replace('/[\\\\\\/\\:\\*\\?\\"<>\\|]/u', '_', $user->name);

        $fileName = sprintf(
            '%s_日報_%s-%s.pdf',
            $safeUserName,
            $start->format('Ymd'),
            $end->format('Ymd')
        );

        return $this->pdfService->downloadForUser($user, $start, $end, $fileName);
    }
}