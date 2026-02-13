<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkRuleController extends Controller
{
    public function edit()
    {
        $rule = WorkRule::firstOrCreate(
            ['name' => '通常勤務'],
            [
                'work_start' => '09:00',
                'work_end'   => '18:00',
                'break_start'=> '12:00',
                'break_end'  => '13:00',

                // ✅ 追加（初回作成時のデフォルト）
                'rounding_unit_minutes' => 15,
            ]
        );

        return Inertia::render('Admin/WorkRules/Edit', [
            'rule' => [
                'id' => $rule->id,
                'name' => $rule->name,
                'work_start' => $rule->work_start,
                'work_end' => $rule->work_end,
                'break_start' => $rule->break_start,
                'break_end' => $rule->break_end,

                // ✅ 追加（フロントに渡す）
                'rounding_unit_minutes' => $rule->rounding_unit_minutes,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->merge([
            'work_start'  => $request->input('work_start')  === '' ? null : $request->input('work_start'),
            'work_end'    => $request->input('work_end')    === '' ? null : $request->input('work_end'),
            'break_start' => $request->input('break_start') === '' ? null : $request->input('break_start'),
            'break_end'   => $request->input('break_end')   === '' ? null : $request->input('break_end'),

            // ✅ 追加（select事故対策：空文字→null）
            'rounding_unit_minutes' => $request->input('rounding_unit_minutes') === '' ? null : $request->input('rounding_unit_minutes'),
        ]);

        $validated = $request->validate([
            'work_start'  => ['nullable', 'date_format:H:i'],
            'work_end'    => ['nullable', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end'   => ['nullable', 'date_format:H:i'],

            // ✅ 追加（許可する単位だけ）
            'rounding_unit_minutes' => ['nullable', 'integer', 'in:1,5,10,15,30,60'],
        ]);

        $rule = WorkRule::where('name', '通常勤務')->firstOrFail();

        // ✅ 追加：nullで来たら現状維持（もしくは15にする）
        if (!array_key_exists('rounding_unit_minutes', $validated) || $validated['rounding_unit_minutes'] === null) {
            $validated['rounding_unit_minutes'] = $rule->rounding_unit_minutes ?? 15;
        }

        $rule->update($validated);

        return back()->with('success', '勤務ルールを更新しました。');
    }
}
