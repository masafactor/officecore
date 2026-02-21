<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkRuleController extends Controller
{
    private const DEFAULT_ROUNDING = 15;

    public function edit(Request $request)
    {
        // ✅ ルールを用意（無ければ作成）
        $defaults = [
            '通常勤務' => [
                'work_start' => '09:00',
                'work_end'   => '18:00',
                'break_start'=> '12:00',
                'break_end'  => '13:00',
            ],
            '夜勤' => [
                'work_start' => '22:00',
                'work_end'   => '06:00', // 日跨ぎ想定
                'break_start'=> '02:00',
                'break_end'  => '03:00',
            ],
            'バイトシフト1' => [
                'work_start' => '10:00',
                'work_end'   => '15:00',
                'break_start'=> null,
                'break_end'  => null,
            ],
            'バイトシフト2' => [
                'work_start' => '15:00',
                'work_end'   => '20:00',
                'break_start'=> null,
                'break_end'  => null,
            ],
        ];

        foreach ($defaults as $name => $d) {
            WorkRule::firstOrCreate(
                ['name' => $name],
                array_merge($d, ['rounding_unit_minutes' => self::DEFAULT_ROUNDING])
            );
        }

        $rules = WorkRule::orderBy('id')->get();

        // ✅ 選択中ルール（クエリで切替できるようにする：/admin/work-rules?rule=2）
        $selectedId = (int) $request->query('rule', 0);
        $selected = $selectedId
            ? $rules->firstWhere('id', $selectedId)
            : $rules->firstWhere('name', '通常勤務') ?? $rules->first();

        return Inertia::render('Admin/WorkRules/Edit', [
            'rules' => $rules->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
            ])->values(),

            'selectedRule' => $selected ? [
                'id' => $selected->id,
                'name' => $selected->name,
                'work_start' => $selected->work_start,
                'work_end' => $selected->work_end,
                'break_start' => $selected->break_start,
                'break_end' => $selected->break_end,
                'rounding_unit_minutes' => $selected->rounding_unit_minutes,
            ] : null,
        ]);
    }

    public function update(Request $request, WorkRule $workRule)
    {
        // 空文字→null
        $request->merge([
            'work_start'  => $request->input('work_start')  === '' ? null : $request->input('work_start'),
            'work_end'    => $request->input('work_end')    === '' ? null : $request->input('work_end'),
            'break_start' => $request->input('break_start') === '' ? null : $request->input('break_start'),
            'break_end'   => $request->input('break_end')   === '' ? null : $request->input('break_end'),
            'rounding_unit_minutes' => $request->input('rounding_unit_minutes') === '' ? null : $request->input('rounding_unit_minutes'),
        ]);

        $validated = $request->validate([
            'work_start'  => ['nullable', 'date_format:H:i'],
            'work_end'    => ['nullable', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end'   => ['nullable', 'date_format:H:i'],
            'rounding_unit_minutes' => ['nullable', 'integer', 'in:1,5,10,15,30,60'],
        ]);

        // rounding が null のときは現状維持（もしくはデフォルト）
        if (!array_key_exists('rounding_unit_minutes', $validated) || $validated['rounding_unit_minutes'] === null) {
            $validated['rounding_unit_minutes'] = $workRule->rounding_unit_minutes ?? self::DEFAULT_ROUNDING;
        }

        $workRule->update($validated);

        return back()->with('success', '勤務ルールを更新しました。');
    }
}