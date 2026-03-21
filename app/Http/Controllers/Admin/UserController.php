<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

// ✅ 追加
use App\Models\EmploymentType;
use App\Models\UserEmployment;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'role')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

public function edit(User $user)
{
    // 勤務ルール履歴
    $user->load(['userWorkRules.workRule' => fn ($q) => $q->select('id', 'name')]);

    $workRules = WorkRule::query()
        ->select('id', 'name')
        ->orderBy('id')
        ->get();

    $currentWorkRule = $user->currentWorkRule(now());

    $workRuleHistories = $user->userWorkRules()
        ->with('workRule:id,name')
        ->orderByDesc('start_date')
        ->get()
        ->map(fn ($h) => [
            'id' => $h->id,
            'work_rule_id' => $h->work_rule_id,
            'work_rule_name' => $h->workRule?->name,
            'start_date' => $h->start_date?->toDateString(),
            'end_date' => $h->end_date?->toDateString(),
        ]);

    // 雇用形態マスタ
    $employmentTypes = EmploymentType::query()
        ->select('id', 'code', 'name')
        ->orderBy('id')
        ->get();

    // 賃金テーブルマスタ
    $wageTables = \App\Models\WageTable::query()
        ->select('id', 'employment_type_id', 'code', 'name', 'hourly_wage')
        ->orderBy('id')
        ->get();

    $today = Carbon::today()->toDateString();

    // 現在有効な雇用情報
    $currentEmployment = UserEmployment::query()
        ->with(['employmentType:id,code,name', 'wageTable:id,employment_type_id,code,name,hourly_wage'])
        ->where('user_id', $user->id)
        ->where('start_date', '<=', $today)
        ->where(function ($query) use ($today) {
            $query->whereNull('end_date')
                ->orWhere('end_date', '>=', $today);
        })
        ->first();

    // 雇用履歴
    $employmentHistories = $user->userEmployments()
        ->with(['employmentType:id,code,name', 'wageTable:id,employment_type_id,code,name,hourly_wage'])
        ->orderByDesc('start_date')
        ->get()
        ->map(fn ($h) => [
            'id' => $h->id,
            'employment_type_id' => $h->employment_type_id,
            'employment_type_name' => $h->employmentType?->name,
            'wage_table_id' => $h->wage_table_id,
            'wage_table_name' => $h->wageTable?->name,
            'hourly_wage' => $h->wageTable?->hourly_wage,
            'start_date' => $h->start_date?->toDateString(),
            'end_date' => $h->end_date?->toDateString(),
        ]);

    return Inertia::render('Admin/Users/Edit', [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'department_id' => $user->department_id,
        ],

        // 勤務ルール
        'workRules' => $workRules,
        'currentWorkRule' => $currentWorkRule ? [
            'id' => $currentWorkRule->id,
            'name' => $currentWorkRule->name,
        ] : null,
        'histories' => $workRuleHistories,

        // 雇用形態
        'employmentTypes' => $employmentTypes,
        'wageTables' => $wageTables,
        'currentEmployment' => $currentEmployment ? [
            'id' => $currentEmployment->id,
            'employment_type_id' => $currentEmployment->employment_type_id,
            'employment_type_code' => $currentEmployment->employmentType?->code,
            'employment_type_name' => $currentEmployment->employmentType?->name,
            'wage_table_id' => $currentEmployment->wage_table_id,
            'wage_table_name' => $currentEmployment->wageTable?->name,
            'hourly_wage' => $currentEmployment->wageTable?->hourly_wage,
            'start_date' => $currentEmployment->start_date?->toDateString(),
            'end_date' => $currentEmployment->end_date?->toDateString(),
        ] : null,
        'employmentHistories' => $employmentHistories->values(),
    ]);
}

    public function updateWorkRule(Request $request, User $user)
    {
        $validated = $request->validate([
            'work_rule_id' => ['required', 'exists:work_rules,id'],
            'start_date'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();

        // ① 開始日以降に既存履歴があるなら弾く（事故防止）
        $conflict = $user->userWorkRules()
            ->where('start_date', '>=', $start->toDateString())
            ->exists();

        if ($conflict) {
            return back()->with('error', 'その開始日以降に既存の勤務ルール履歴があります。開始日を見直してください。');
        }

        // ② 直前の履歴（開始日より前で一番新しい）を閉じる
        $prev = $user->userWorkRules()
            ->where('start_date', '<=', $start->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if ($prev) {
            if ($prev->end_date === null || Carbon::parse($prev->end_date)->gte($start)) {
                $prev->end_date = $start->copy()->subDay()->toDateString();
                $prev->save();
            }
        }

        // ③ 新しい履歴を追加（end_date=null）
        $user->userWorkRules()->create([
            'work_rule_id' => (int)$validated['work_rule_id'],
            'start_date'   => $start->toDateString(),
            'end_date'     => null,
        ]);

        return back()->with('success', '勤務ルールを割り当てました。');
    }

    // ✅ 追加：雇用形態も同じ方針で
    // public function updateEmployment(Request $request, User $user)
    // {
    //     $validated = $request->validate([
    //         'employment_type_id' => ['required', 'exists:employment_types,id'],
    //         'start_date'         => ['required', 'date'],
    //     ]);

    //     $start = Carbon::parse($validated['start_date'])->startOfDay();

    //     // ① 開始日以降に既存履歴があるなら弾く（事故防止）
    //     $conflict = $user->userEmployments()
    //         ->where('start_date', '>=', $start->toDateString())
    //         ->exists();

    //     if ($conflict) {
    //         return back()->with('error', 'その開始日以降に既存の雇用形態履歴があります。開始日を見直してください。');
    //     }

    //     // ② 直前の履歴（開始日より前で一番新しい）を閉じる
    //     $prev = $user->userEmployments()
    //         ->where('start_date', '<=', $start->toDateString())
    //         ->orderByDesc('start_date')
    //         ->first();

    //     if ($prev) {
    //         if ($prev->end_date === null || Carbon::parse($prev->end_date)->gte($start)) {
    //             $prev->end_date = $start->copy()->subDay()->toDateString();
    //             $prev->save();
    //         }
    //     }

    //     // ③ 新しい履歴を追加（end_date=null）
    //     $user->userEmployments()->create([
    //         'employment_type_id' => (int)$validated['employment_type_id'],
    //         'start_date'         => $start->toDateString(),
    //         'end_date'           => null,
    //     ]);

    //     return back()->with('success', '雇用形態を更新しました。');
    // }

    public function updateEmployment(Request $request, User $user)
    {
        $validated = $request->validate([
            'employment_type_id' => ['required', 'exists:employment_types,id'],
            'wage_table_id'      => ['nullable', 'exists:wage_tables,id'],
            'start_date'         => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();

        $conflict = $user->userEmployments()
            ->where('start_date', '>=', $start->toDateString())
            ->exists();

        if ($conflict) {
            return back()->with('error', 'その開始日以降に既存の雇用形態履歴があります。開始日を見直してください。');
        }

        $prev = $user->userEmployments()
            ->where('start_date', '<=', $start->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if ($prev) {
            if ($prev->end_date === null || Carbon::parse($prev->end_date)->gte($start)) {
                $prev->end_date = $start->copy()->subDay()->toDateString();
                $prev->save();
            }
        }

        $user->userEmployments()->create([
            'employment_type_id' => (int) $validated['employment_type_id'],
            'wage_table_id'      => $validated['wage_table_id'] ?: null,
            'start_date'         => $start->toDateString(),
            'end_date'           => null,
        ]);

        return back()->with('success', '雇用形態を更新しました。');
    }
}