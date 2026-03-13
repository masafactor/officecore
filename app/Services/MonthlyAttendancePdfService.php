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

    public function downloadBulk(array $reports, string $fileName = 'monthly-attendance-bulk.pdf'): Response
    {
        $pdf = Pdf::loadView('pdf.monthly-attendance-bulk', [
            'reports' => $reports,
        ]);

        return $pdf->download($fileName);
    }
}