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

        // ✅ 雇用形態マスタ
        $employmentTypes = EmploymentType::query()
            ->select('id', 'code', 'name')
            ->orderBy('id')
            ->get();

        // ✅ 現在有効（今日 기준）
        // どっちでもOK。既に user 側にある方を使ってね。
        // $currentEmployment = $user->employmentOn(now()); // EmploymentType を返す想定
        $currentEmployment = method_exists($user, 'employmentOn')
            ? $user->employmentOn(now())
            : null;

        // ✅ 履歴（新しい順）
        $employmentHistories = method_exists($user, 'userEmployments')
            ? $user->userEmployments()
                ->with('employmentType:id,code,name')
                ->orderByDesc('start_date')
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'employment_type_id' => $h->employment_type_id,
                    'employment_type_name' => $h->employmentType?->name,
                    'employment_type_code' => $h->employmentType?->code,
                    'start_date' => $h->start_date?->toDateString(),
                    'end_date' => $h->end_date?->toDateString(),
                ])
            : collect();

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'staff',
            ],

            // 勤務ルール
            'workRules' => $workRules,
            'currentWorkRule' => $currentWorkRule ? ['id' => $currentWorkRule->id, 'name' => $currentWorkRule->name] : null,
            'histories' => $workRuleHistories,

            // ✅ 雇用形態
            'employmentTypes' => $employmentTypes,
            'currentEmployment' => $currentEmployment ? [
                'id' => $currentEmployment->id,
                'code' => $currentEmployment->code,
                'name' => $currentEmployment->name,
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
    public function updateEmployment(Request $request, User $user)
    {
        $validated = $request->validate([
            'employment_type_id' => ['required', 'exists:employment_types,id'],
            'start_date'         => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();

        // ① 開始日以降に既存履歴があるなら弾く（事故防止）
        $conflict = $user->userEmployments()
            ->where('start_date', '>=', $start->toDateString())
            ->exists();

        if ($conflict) {
            return back()->with('error', 'その開始日以降に既存の雇用形態履歴があります。開始日を見直してください。');
        }

        // ② 直前の履歴（開始日より前で一番新しい）を閉じる
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

        // ③ 新しい履歴を追加（end_date=null）
        $user->userEmployments()->create([
            'employment_type_id' => (int)$validated['employment_type_id'],
            'start_date'         => $start->toDateString(),
            'end_date'           => null,
        ]);

        return back()->with('success', '雇用形態を更新しました。');
    }
}