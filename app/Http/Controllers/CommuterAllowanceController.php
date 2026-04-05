<?php

namespace App\Http\Controllers;

use App\Models\CommuterAllowance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommuterAllowanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $allowances = CommuterAllowance::query()
            ->where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($allowance) {
                return [
                    'id' => $allowance->id,
                    'start_date' => optional($allowance->start_date)->toDateString(),
                    'end_date' => optional($allowance->end_date)->toDateString(),
                    'from_place' => $allowance->from_place,
                    'to_place' => $allowance->to_place,
                    'amount' => $allowance->amount,
                    'pass_type' => $allowance->pass_type,
                    'status' => $allowance->status,
                    'note' => $allowance->note,
                ];
            });

        return Inertia::render('CommuterAllowances/Index', [
            'allowances' => $allowances,
        ]);
    }

    public function create()
    {
        return Inertia::render('CommuterAllowances/Create', [
            'passTypeOptions' => [
                ['value' => 'monthly', 'label' => '1か月'],
                ['value' => 'three_month', 'label' => '3か月'],
                ['value' => 'six_month', 'label' => '6か月'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'from_place' => ['required', 'string', 'max:255'],
            'to_place' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'pass_type' => ['required', 'in:monthly,three_month,six_month'],
            'note' => ['nullable', 'string'],
        ]);

        CommuterAllowance::create([
            'user_id' => $request->user()->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'from_place' => $validated['from_place'],
            'to_place' => $validated['to_place'],
            'amount' => $validated['amount'],
            'pass_type' => $validated['pass_type'],
            'status' => 'pending',
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()
            ->route('commuter-allowances.index')
            ->with('success', '通勤定期を登録しました。');
    }
}