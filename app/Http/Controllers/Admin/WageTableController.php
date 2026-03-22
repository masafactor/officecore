<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmploymentType;
use App\Models\WageTable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WageTableController extends Controller
{
    public function index()
    {
        $wageTables = WageTable::query()
            ->with('employmentType:id,code,name')
            ->orderBy('employment_type_id')
            ->orderBy('id')
            ->get()
            ->map(fn ($wageTable) => [
                'id' => $wageTable->id,
                'code' => $wageTable->code,
                'name' => $wageTable->name,
                'hourly_wage' => $wageTable->hourly_wage,
                'start_date' => $wageTable->start_date?->toDateString(),
                'end_date' => $wageTable->end_date?->toDateString(),
                'employment_type' => $wageTable->employmentType ? [
                    'id' => $wageTable->employmentType->id,
                    'code' => $wageTable->employmentType->code,
                    'name' => $wageTable->employmentType->name,
                ] : null,
            ]);

        return Inertia::render('Admin/WageTables/Index', [
            'wageTables' => $wageTables,
        ]);
    }

    public function create()
    {
        $employmentTypes = EmploymentType::query()
            ->select('id', 'code', 'name')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/WageTables/Create', [
            'employmentTypes' => $employmentTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employment_type_id' => ['required', 'exists:employment_types,id'],
            'code' => ['required', 'string', 'max:255', 'unique:wage_tables,code'],
            'name' => ['required', 'string', 'max:255'],
            'hourly_wage' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        WageTable::create([
            'employment_type_id' => (int) $validated['employment_type_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'hourly_wage' => (int) $validated['hourly_wage'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return redirect()
            ->route('admin.wage-tables.index')
            ->with('success', '賃金テーブルを作成しました。');
    }

    public function edit(WageTable $wageTable)
    {
        $employmentTypes = EmploymentType::query()
            ->select('id', 'code', 'name')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/WageTables/Edit', [
            'wageTable' => [
                'id' => $wageTable->id,
                'employment_type_id' => $wageTable->employment_type_id,
                'code' => $wageTable->code,
                'name' => $wageTable->name,
                'hourly_wage' => $wageTable->hourly_wage,
                'start_date' => $wageTable->start_date?->toDateString(),
                'end_date' => $wageTable->end_date?->toDateString(),
            ],
            'employmentTypes' => $employmentTypes,
        ]);
    }

    public function update(Request $request, WageTable $wageTable)
    {
        $validated = $request->validate([
            'employment_type_id' => ['required', 'exists:employment_types,id'],
            'code' => ['required', 'string', 'max:255', 'unique:wage_tables,code,' . $wageTable->id],
            'name' => ['required', 'string', 'max:255'],
            'hourly_wage' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $wageTable->update([
            'employment_type_id' => (int) $validated['employment_type_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'hourly_wage' => (int) $validated['hourly_wage'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return redirect()
            ->route('admin.wage-tables.index')
            ->with('success', '賃金テーブルを更新しました。');
    }

    public function destroy(WageTable $wageTable)
    {
        $isUsed = $wageTable->userEmployments()->exists();

        if ($isUsed) {
            return redirect()
                ->route('admin.wage-tables.index')
                ->with('error', '使用中の賃金テーブルは削除できません。');
        }

        $wageTable->delete();

        return redirect()
            ->route('admin.wage-tables.index')
            ->with('success', '賃金テーブルを削除しました。');
    }
}