<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->toDateString();

        // 選択日の既存レポート（なければnull）
        $current = DailyReport::query()
            ->where('user_id', $user->id)
            ->whereDate('report_date', $date)
            ->first();

        // 履歴（最新順。最小構成なので自分の分だけ）
        $history = DailyReport::query()
            ->where('user_id', $user->id)
            ->orderByDesc('report_date')
            ->limit(30)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'report_date' => $r->report_date->toDateString(),
                'status' => $r->status,
                'content' => $r->content,
                'updated_at' => optional($r->updated_at)->toDateTimeString(),
            ]);

        return Inertia::render('DailyReports/Index', [
            'date' => $date,
            'current' => $current ? [
                'id' => $current->id,
                'report_date' => $current->report_date->toDateString(),
                'status' => $current->status,
                'content' => $current->content,
                'updated_at' => optional($current->updated_at)->toDateTimeString(),
            ] : null,
            'history' => $history,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'report_date' => ['required', 'date'],
            'content' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($validated['report_date'])->toDateString();

        DailyReport::updateOrCreate(
            ['user_id' => $user->id, 'report_date' => $date],
            ['content' => $validated['content'] ?? null, 'status' => 'draft']
        );

        return redirect()->route('daily-reports.index', ['date' => $date])
            ->with('success', '日報を保存しました。');
    }
}
