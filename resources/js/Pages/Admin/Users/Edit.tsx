import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, usePage, Link } from '@inertiajs/react'

type Props = {
  user: { id: number; name: string; email: string; role: string }

  // 勤務ルール
  workRules: { id: number; name: string }[]
  currentWorkRule: { id: number; name: string } | null
  histories: {
    id: number
    work_rule_id: number
    work_rule_name: string | null
    start_date: string
    end_date: string | null
  }[]

  // ✅ 雇用形態
  employmentTypes: { id: number; code: string; name: string }[]
  currentEmployment: { id: number; code: string; name: string } | null
  employmentHistories: {
    id: number
    employment_type_id: number
    employment_type_name: string | null
    employment_type_code: string | null
    start_date: string
    end_date: string | null
  }[]
}

export default function Edit({
  user,
  workRules,
  currentWorkRule,
  histories,
  employmentTypes,
  currentEmployment,
  employmentHistories,
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
    employment_type_id: currentEmployment?.id ? String(currentEmployment.id) : '',
    start_date: new Date().toISOString().slice(0, 10),
  })

  const submitEmployment = (e: React.FormEvent) => {
    e.preventDefault()
    employmentForm.patch(route('admin.users.employment.update', user.id))
  }

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
                    現在の雇用形態：{currentEmployment ? currentEmployment.name : '未設定'}
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
                          onChange={(e) => employmentForm.setData('employment_type_id', e.target.value)}
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
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}