<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceClosing;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceClosingController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->input('year', now()->year));
        $month = (int) ($request->input('month', now()->subMonthNoOverflow()->month));

        $closings = AttendanceClosing::query()
            ->with(['user:id,name', 'approver:id,name'])
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('status')
            ->orderBy('user_id')
            ->get()
            ->map(function ($closing) {
                return [
                    'id' => $closing->id,
                    'user_id' => $closing->user_id,
                    'user_name' => $closing->user?->name ?? '不明',
                    'year' => $closing->year,
                    'month' => $closing->month,
                    'status' => $closing->status,
                    'submitted_at' => $closing->submitted_at?->format('Y-m-d H:i:s'),
                    'approved_at' => $closing->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $closing->approved_by,
                    'approved_by_name' => $closing->approver?->name,
                ];
            });

        return Inertia::render('Admin/AttendanceClosings/Index', [
            'filters' => [
                'year' => $year,
                'month' => $month,
            ],
            'closings' => $closings,
        ]);
    }
}