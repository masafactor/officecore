import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, usePage, Link } from '@inertiajs/react'


type CurrentSalary = {
  id: number
  base_salary: number
  start_date: string | null
  end_date: string | null
  reason: string | null
  note: string | null
} | null

type SalaryHistory = {
  id: number
  base_salary: number
  start_date: string | null
  end_date: string | null
  reason: string | null
  note: string | null
}


type Props = {
  user: { id: number; name: string; email: string; role: string }

  workRules: { id: number; name: string }[]
  currentWorkRule: { id: number; name: string } | null
  histories: {
    id: number
    work_rule_id: number
    work_rule_name: string | null
    start_date: string
    end_date: string | null
  }[]

  employmentTypes: { id: number; code: string; name: string }[]
  wageTables: {
    id: number
    employment_type_id: number
    code: string
    name: string
    hourly_wage: number
  }[]
  currentEmployment: {
    id: number
    employment_type_id: number
    employment_type_code: string | null
    employment_type_name: string | null
    wage_table_id: number | null
    wage_table_name: string | null
    hourly_wage: number | null
    start_date: string | null
    end_date: string | null
  } | null
  employmentHistories: {
    id: number
    employment_type_id: number
    employment_type_name: string | null
    employment_type_code: string | null
    wage_table_id: number | null
    wage_table_name: string | null
    hourly_wage: number | null
    start_date: string
    end_date: string | null
  }[]

  currentSalary: CurrentSalary
  salaryHistories: SalaryHistory[]
}





export default function Edit({
  user,
  workRules,
  currentWorkRule,
  histories,
  employmentTypes,
  currentEmployment,
  employmentHistories,
  wageTables,
  currentSalary,
  salaryHistories,
}: Props) {
  const flash = usePage<any>().props.flash

  // 勤務ルール割当フォーム
  const workRuleForm = useForm({
    work_rule_id: currentWorkRule?.id ? String(currentWorkRule.id) : '',
    start_date: new Date().toISOString().slice(0, 10),
  })

  const submitWorkRule = (e: React.FormEvent) => {
    e.preventDefault()
    workRuleForm.patch(route('admin.users.work-rule.update', user.id))
  }

  // ✅ 雇用形態割当フォーム
  const employmentForm = useForm({
    employment_type_id: currentEmployment?.employment_type_id
      ? String(currentEmployment.employment_type_id)
      : '',
    wage_table_id: currentEmployment?.wage_table_id
      ? String(currentEmployment.wage_table_id)
      : '',
    start_date: new Date().toISOString().slice(0, 10),
  })

  const submitEmployment = (e: React.FormEvent) => {
    e.preventDefault()
    employmentForm.patch(route('admin.users.employment.update', user.id))
  }

  const filteredWageTables = wageTables.filter(
    (w) => String(w.employment_type_id) === employmentForm.data.employment_type_id
  )

  const salaryForm = useForm({
  base_salary: currentSalary?.base_salary ? String(currentSalary.base_salary) : '',
  start_date: new Date().toISOString().slice(0, 10),
  reason: '',
  note: '',
  })

  const submitSalary = (e: React.FormEvent) => {
    e.preventDefault()

    salaryForm.post(route('admin.users.update-salary', user.id), {
      preserveScroll: true,
    })
  }

  const selectedEmploymentType = employmentTypes.find(
  (type) => String(type.id) === employmentForm.data.employment_type_id
  )

  const isFullTime =
  selectedEmploymentType?.code === 'regular' ||
  selectedEmploymentType?.code === 'full_time'



  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">ユーザー編集</h2>}>
      <Head title="ユーザー編集" />

      <div className="py-12">
        <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
          <div className="mb-4">
            <Link className="text-sm text-blue-600 hover:underline" href={route('admin.users.index')}>
              ← 一覧へ戻る
            </Link>
          </div>

          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 space-y-8">
              {(flash?.success || flash?.error) && (
                <div className="space-y-2">
                  {flash?.success && (
                    <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>
                  )}
                  {flash?.error && <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}
                </div>
              )}

              {/* user info */}
              <div className="space-y-1">
                <div className="text-sm text-gray-700">ID: {user.id}</div>
                <div className="text-sm text-gray-700">名前: {user.name}</div>
                <div className="text-sm text-gray-700">Email: {user.email}</div>
                <div className="text-sm text-gray-700">Role: {user.role}</div>
              </div>

              {/* current */}
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div className="rounded-md bg-gray-50 p-4">
                  <div className="text-sm text-gray-700">
                    現在の勤務ルール：{currentWorkRule ? currentWorkRule.name : '未設定'}
                  </div>
                </div>
                <div className="rounded-md bg-gray-50 p-4">
                  <div className="text-sm text-gray-700">
                    現在の雇用形態：{currentEmployment ? currentEmployment.employment_type_name : '未設定'}
                  </div>
                </div>
              </div>

              {/* forms */}
              <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                {/* work rule */}
                <div className="rounded-lg border p-4 space-y-4">
                  <div className="text-sm font-semibold text-gray-800">勤務ルール割当</div>

                  <form onSubmit={submitWorkRule} className="space-y-4">
                    <div className="grid grid-cols-1 gap-4">
                      <div>
                        <label className="block text-xs text-gray-600">勤務ルール</label>
                        <select
                          value={workRuleForm.data.work_rule_id}
                          onChange={(e) => workRuleForm.setData('work_rule_id', e.target.value)}
                          className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        >
                          <option value="">選択してください</option>
                          {workRules.map((r) => (
                            <option key={r.id} value={String(r.id)}>
                              {r.name}
                            </option>
                          ))}
                        </select>
                        {workRuleForm.errors.work_rule_id && (
                          <div className="mt-1 text-xs text-red-600">{workRuleForm.errors.work_rule_id}</div>
                        )}
                      </div>
                      

                      <div>
                        <label className="block text-xs text-gray-600">適用開始日</label>
                        <input
                          type="date"
                          value={workRuleForm.data.start_date}
                          onChange={(e) => workRuleForm.setData('start_date', e.target.value)}
                          className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        />
                        {workRuleForm.errors.start_date && (
                          <div className="mt-1 text-xs text-red-600">{workRuleForm.errors.start_date}</div>
                        )}
                      </div>
                    </div>

                    <button
                      type="submit"
                      disabled={workRuleForm.processing}
                      className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
                    >
                      割り当て
                    </button>

                    <div className="text-xs text-gray-500">
                      ※ 開始日以降に履歴がある場合は安全のため更新を拒否します（最小版）。
                    </div>
                  </form>
                </div>

                {/* employment */}
                <div className="rounded-lg border p-4 space-y-4">
                  <div className="text-sm font-semibold text-gray-800">雇用形態割当</div>

                  <form onSubmit={submitEmployment} className="space-y-4">
                    <div className="grid grid-cols-1 gap-4">
                      <div>
                        <label className="block text-xs text-gray-600">雇用形態</label>
                        <select
                          value={employmentForm.data.employment_type_id}
                          onChange={(e) => {
                            employmentForm.setData('employment_type_id', e.target.value)
                            employmentForm.setData('wage_table_id', '')
                          }}
                          className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        >
                          <option value="">選択してください</option>
                          {employmentTypes.map((t) => (
                            <option key={t.id} value={String(t.id)}>
                              {t.name}（{t.code}）
                            </option>
                          ))}
                        </select>
                        {employmentForm.errors.employment_type_id && (
                          <div className="mt-1 text-xs text-red-600">{employmentForm.errors.employment_type_id}</div>
                        )}
                      </div>
                      <div>
                        <label className="block text-xs text-gray-600">賃金テーブル</label>
                        <select
                          value={employmentForm.data.wage_table_id}
                          onChange={(e) => employmentForm.setData('wage_table_id', e.target.value)}
                          className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        >
                          <option value="">選択してください</option>
                          {filteredWageTables.map((t) => (
                            <option key={t.id} value={String(t.id)}>
                              {t.name}（{t.hourly_wage}円）
                            </option>
                          ))}
                        </select>
                        {employmentForm.errors.wage_table_id && (
                          <div className="mt-1 text-xs text-red-600">{employmentForm.errors.wage_table_id}</div>
                        )}
                      </div>

                      <div>
                        <label className="block text-xs text-gray-600">適用開始日</label>
                        <input
                          type="date"
                          value={employmentForm.data.start_date}
                          onChange={(e) => employmentForm.setData('start_date', e.target.value)}
                          className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        />
                        {employmentForm.errors.start_date && (
                          <div className="mt-1 text-xs text-red-600">{employmentForm.errors.start_date}</div>
                        )}
                      </div>
                    </div>

                    <button
                      type="submit"
                      disabled={employmentForm.processing}
                      className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
                    >
                      割り当て
                    </button>

                    <div className="text-xs text-gray-500">
                      ※ 開始日以降に履歴がある場合は安全のため更新を拒否します（最小版）。
                    </div>
                  </form>
                </div>
              </div>

              {/* histories */}
              <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                {/* work rule history */}
                <div>
                  <div className="text-sm font-semibold text-gray-800 mb-2">勤務ルール履歴</div>
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="text-left text-gray-600">
                        <th className="py-2">ルール</th>
                        <th>開始</th>
                        <th>終了</th>
                      </tr>
                    </thead>
                    <tbody>
                      {histories.map((h) => (
                        <tr key={h.id} className="border-t">
                          <td className="py-2">{h.work_rule_name ?? `#${h.work_rule_id}`}</td>
                          <td>{h.start_date}</td>
                          <td>{h.end_date ?? '—'}</td>
                        </tr>
                      ))}
                      {histories.length === 0 && (
                        <tr className="border-t">
                          <td className="py-2 text-gray-500" colSpan={3}>
                            履歴がありません
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>

                {/* employment history */}
                <div>
                  <div className="text-sm font-semibold text-gray-800 mb-2">雇用形態履歴</div>
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="text-left text-gray-600">
                        <th className="py-2">雇用形態</th>
                        <th>開始</th>
                        <th>終了</th>
                      </tr>
                    </thead>
                    <tbody>
                      {employmentHistories.map((h) => (
                        <tr key={h.id} className="border-t">
                          <td className="py-2">
                            {h.employment_type_name ?? `#${h.employment_type_id}`}
                            {h.employment_type_code ? <span className="text-gray-400">（{h.employment_type_code}）</span> : null}
                          </td>
                          <td>{h.start_date}</td>
                          <td>{h.end_date ?? '—'}</td>
                        </tr>
                      ))}
                      {employmentHistories.length === 0 && (
                        <tr className="border-t">
                          <td className="py-2 text-gray-500" colSpan={3}>
                            履歴がありません
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>

              {/* small note */}
              <div className="text-xs text-gray-500">
                ※「開始日以降の履歴があると弾く」方針は事故防止のため。将来は “途中挿入” を実装するならここを拡張。
              </div>

              {isFullTime && (
                  <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <h3 className="mb-4 text-base font-semibold text-gray-800">固定給設定</h3>

                    <form onSubmit={submitSalary} className="space-y-4">
                      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                          <label className="mb-1 block text-sm font-medium text-gray-700">固定給</label>
                          <input
                            type="number"
                            min="0"
                            value={salaryForm.data.base_salary}
                            onChange={(e) => salaryForm.setData('base_salary', e.target.value)}
                            className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                          />
                          {salaryForm.errors.base_salary && (
                            <div className="mt-1 text-xs text-red-600">{salaryForm.errors.base_salary}</div>
                          )}
                        </div>

                        <div>
                          <label className="mb-1 block text-sm font-medium text-gray-700">適用開始日</label>
                          <input
                            type="date"
                            value={salaryForm.data.start_date}
                            onChange={(e) => salaryForm.setData('start_date', e.target.value)}
                            className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                          />
                          {salaryForm.errors.start_date && (
                            <div className="mt-1 text-xs text-red-600">{salaryForm.errors.start_date}</div>
                          )}
                        </div>

                        <div>
                          <label className="mb-1 block text-sm font-medium text-gray-700">理由</label>
                          <input
                            type="text"
                            value={salaryForm.data.reason}
                            onChange={(e) => salaryForm.setData('reason', e.target.value)}
                            placeholder="例: 初期設定 / 定期昇給"
                            className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                          />
                          {salaryForm.errors.reason && (
                            <div className="mt-1 text-xs text-red-600">{salaryForm.errors.reason}</div>
                          )}
                        </div>
                      </div>

                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">メモ</label>
                        <textarea
                          value={salaryForm.data.note}
                          onChange={(e) => salaryForm.setData('note', e.target.value)}
                          rows={3}
                          className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        {salaryForm.errors.note && (
                          <div className="mt-1 text-xs text-red-600">{salaryForm.errors.note}</div>
                        )}
                      </div>

                      {currentSalary && (
                        <div className="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700">
                          <div className="font-semibold text-gray-800">現在の固定給</div>
                          <div className="mt-2 space-y-1">
                            <div>固定給: {currentSalary.base_salary.toLocaleString()}円</div>
                            <div>開始日: {currentSalary.start_date ?? '—'}</div>
                            <div>終了日: {currentSalary.end_date ?? '—'}</div>
                            <div>理由: {currentSalary.reason ?? '—'}</div>
                          </div>
                        </div>
                      )}

                      <button
                        type="submit"
                        disabled={salaryForm.processing}
                        className="inline-flex h-11 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                      >
                        固定給を設定
                      </button>
                    </form>
                  </section>
                )}
            </div>
    
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}