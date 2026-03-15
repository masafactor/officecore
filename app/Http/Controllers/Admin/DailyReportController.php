<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $query = DailyReport::query()
            ->with('user:id,name')
            ->when(!empty($filters['date']), function ($q) use ($filters) {
                $q->whereDate('report_date', $filters['date']);
            })
            ->when(!empty($filters['user_id']), function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->when(!empty($filters['keyword']), function ($q) use ($filters) {
                $keyword = $filters['keyword'];
                $q->where('content', 'like', "%{$keyword}%");
            })
            ->orderByDesc('report_date')
            ->orderByDesc('updated_at');

        $dailyReports = $query->paginate(20)->withQueryString();

        $users = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/DailyReports/Index', [
            'filters' => [
                'date' => $filters['date'] ?? '',
                'user_id' => isset($filters['user_id']) ? (string) $filters['user_id'] : '',
                'keyword' => $filters['keyword'] ?? '',
            ],
            'users' => $users,
            'dailyReports' => $dailyReports->through(function (DailyReport $report) {
                return [
                    'id' => $report->id,
                    'report_date' => optional($report->report_date)->format('Y-m-d'),
                    'status' => $report->status,
                    'content' => $report->content,
                    'updated_at' => optional($report->updated_at)?->format('Y-m-d H:i'),
                    'user' => [
                        'id' => $report->user?->id,
                        'name' => $report->user?->name,
                    ],
                ];
            }),
        ]);
    }

    public function show(DailyReport $dailyReport)
    {
        $dailyReport->load('user:id,name');

        return Inertia::render('Admin/DailyReports/Show', [
            'dailyReport' => [
                'id' => $dailyReport->id,
                'report_date' => optional($dailyReport->report_date)->format('Y-m-d'),
                'status' => $dailyReport->status,
                'content' => $dailyReport->content,
                'updated_at' => optional($dailyReport->updated_at)?->format('Y-m-d H:i'),
                'user' => [
                    'id' => $dailyReport->user?->id,
                    'name' => $dailyReport->user?->name,
                ],
            ],
        ]);
    }
}