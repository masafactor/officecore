<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class MonthlyAttendancePdfService
{
    public function downloadSingle(array $data, string $fileName = 'monthly-attendance.pdf')
    {
        $pdf = Pdf::loadView('pdf.monthly-attendance', $data)->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }

    public function downloadBulk(array $reports, int $year, int $month): Response
    {
        $filename = sprintf('attendance_bulk_%04d_%02d.pdf', $year, $month);

        return Pdf::loadView('pdf.monthly-attendance-bulk', [
            'reports' => $reports,
            'year' => $year,
            'month' => $month,
        ])->download($filename);
    }
}