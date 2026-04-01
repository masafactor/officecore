<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyCalendarDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyCalendarController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $days = CompanyCalendarDay::query()
            ->whereBetween('calendar_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('calendar_date')
            ->get()
            ->map(fn ($day) => [
                'id' => $day->id,
                'calendar_date' => $day->calendar_date?->toDateString(),
                'day_type' => $day->day_type,
                'scheduled_minutes' => $day->scheduled_minutes,
                'note' => $day->note,
            ])
            ->values();

        return Inertia::render('Admin/CompanyCalendar/Index', [
            'year' => $year,
            'days' => $days,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'calendar_date' => ['required', 'date'],
            'day_type' => ['required', 'in:workday,holiday,shortday'],
            'scheduled_minutes' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        CompanyCalendarDay::updateOrCreate(
            ['calendar_date' => $validated['calendar_date']],
            [
                'day_type' => $validated['day_type'],
                'scheduled_minutes' => (int) $validated['scheduled_minutes'],
                'note' => $validated['note'] ?? null,
            ]
        );

        return back()->with('success', '会社カレンダーを登録・更新しました。');
    }

    public function update(Request $request, CompanyCalendarDay $companyCalendarDay)
    {
        $validated = $request->validate([
            'day_type' => ['required', 'in:workday,holiday,shortday'],
            'scheduled_minutes' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $companyCalendarDay->update($validated);

        return back()->with('success', '会社カレンダーを更新しました。');
    }

    public function generateYear(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = (int) $validated['year'];

        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $isWeekend = in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true);

            CompanyCalendarDay::firstOrCreate(
                ['calendar_date' => $date->toDateString()],
                [
                    'day_type' => $isWeekend ? 'holiday' : 'workday',
                    'scheduled_minutes' => $isWeekend ? 0 : 480,
                    'note' => null,
                ]
            );
        }

        return back()->with('success', "{$year}年の会社カレンダーを生成しました。");
    }


    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'day_type' => ['required', 'in:workday,holiday,shortday'],
            'scheduled_minutes' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date'])->startOfDay();
        $end = \Carbon\Carbon::parse($validated['end_date'])->startOfDay();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            \App\Models\CompanyCalendarDay::updateOrCreate(
                ['calendar_date' => $date->toDateString()],
                [
                    'day_type' => $validated['day_type'],
                    'scheduled_minutes' => (int) $validated['scheduled_minutes'],
                    'note' => $validated['note'] ?? null,
                ]
            );
        }

        return back()->with('success', '会社カレンダーを範囲更新しました。');
    }

    public function updateWeekdays(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'], // 0:日曜 〜 6:土曜
            'day_type' => ['required', 'in:workday,holiday,shortday'],
            'scheduled_minutes' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date'])->startOfDay();
        $end = \Carbon\Carbon::parse($validated['end_date'])->startOfDay();
        $weekdays = array_map('intval', $validated['weekdays']);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!in_array($date->dayOfWeek, $weekdays, true)) {
                continue;
            }

            \App\Models\CompanyCalendarDay::updateOrCreate(
                ['calendar_date' => $date->toDateString()],
                [
                    'day_type' => $validated['day_type'],
                    'scheduled_minutes' => (int) $validated['scheduled_minutes'],
                    'note' => $validated['note'] ?? null,
                ]
            );
        }

        return back()->with('success', '曜日指定で会社カレンダーを更新しました。');
    }
}