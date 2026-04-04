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

    private function nthMonday(int $year, int $month, int $nth): \Carbon\Carbon
    {
        $date = \Carbon\Carbon::create($year, $month, 1)->startOfDay();

        while ($date->dayOfWeek !== \Carbon\Carbon::MONDAY) {
            $date->addDay();
        }

        return $date->addWeeks($nth - 1);
    }


    private function upsertHoliday(string $date, string $holidayName): void
    {
        \App\Models\CompanyCalendarDay::updateOrCreate(
            ['calendar_date' => $date],
            [
                'day_type' => 'holiday',
                'scheduled_minutes' => 0,
                'is_public_holiday' => true,
                'holiday_name' => $holidayName,
                'note' => $holidayName,
            ]
        );
    }

    public function generateHolidays(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = (int) $validated['year'];

        $fixedHolidays = [
            ['date' => "{$year}-01-01", 'name' => '元日'],
            ['date' => "{$year}-02-11", 'name' => '建国記念の日'],
            ['date' => "{$year}-02-23", 'name' => '天皇誕生日'],
            ['date' => "{$year}-04-29", 'name' => '昭和の日'],
            ['date' => "{$year}-05-03", 'name' => '憲法記念日'],
            ['date' => "{$year}-05-04", 'name' => 'みどりの日'],
            ['date' => "{$year}-05-05", 'name' => 'こどもの日'],
            ['date' => "{$year}-08-11", 'name' => '山の日'],
            ['date' => "{$year}-11-03", 'name' => '文化の日'],
            ['date' => "{$year}-11-23", 'name' => '勤労感謝の日'],
        ];

        foreach ($fixedHolidays as $holiday) {
            $this->upsertHoliday($holiday['date'], $holiday['name']);
        }

        $mondayHolidays = [
            ['date' => $this->nthMonday($year, 1, 2)->toDateString(), 'name' => '成人の日'],
            ['date' => $this->nthMonday($year, 7, 3)->toDateString(), 'name' => '海の日'],
            ['date' => $this->nthMonday($year, 9, 3)->toDateString(), 'name' => '敬老の日'],
            ['date' => $this->nthMonday($year, 10, 2)->toDateString(), 'name' => 'スポーツの日'],
        ];

        foreach ($mondayHolidays as $holiday) {
            $this->upsertHoliday($holiday['date'], $holiday['name']);
        }

        return back()->with(
            'success',
            "{$year}年の祝日を自動生成しました（春分の日・秋分の日・振替休日は手動対応）。"
        );
    }

    public function generateSubstituteHolidays(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = (int) $validated['year'];

        $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $end = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

        $holidays = \App\Models\CompanyCalendarDay::query()
            ->whereBetween('calendar_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_public_holiday', true)
            ->orderBy('calendar_date')
            ->get();

        foreach ($holidays as $holiday) {
            $holidayDate = $holiday->calendar_date->copy();

            // 日曜日の祝日だけ対象
            if ($holidayDate->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                continue;
            }

            $substituteDate = $holidayDate->copy()->addDay();

            while (true) {
                $existing = \App\Models\CompanyCalendarDay::query()
                    ->where('calendar_date', $substituteDate->toDateString())
                    ->first();

                // レコードが存在しないなら新規作成
                if (! $existing) {
                    \App\Models\CompanyCalendarDay::create([
                        'calendar_date' => $substituteDate->toDateString(),
                        'day_type' => 'holiday',
                        'scheduled_minutes' => 0,
                        'is_public_holiday' => false,
                        'holiday_name' => '振替休日',
                        'note' => '振替休日',
                    ]);
                    break;
                }

                // 既に祝日なら次の日へ
                if ($existing->day_type === 'holiday') {
                    $substituteDate->addDay();
                    continue;
                }

                // holiday でない日なら振替休日に更新
                $existing->update([
                    'day_type' => 'holiday',
                    'scheduled_minutes' => 0,
                    'is_public_holiday' => false,
                    'holiday_name' => '振替休日',
                    'note' => '振替休日',
                ]);
                break;
            }
        }

        return back()->with('success', "{$year}年の振替休日を作成しました。");
    }
}