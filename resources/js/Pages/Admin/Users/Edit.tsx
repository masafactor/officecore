import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, usePage, Link } from '@inertiajs/react'

type Props = {
  user: { id: number; name: string; email: string; role: string }
  workRules: { id: number; name: string }[]
  currentWorkRule: { id: number; name: string } | null
  histories: { id: number; work_rule_id: number; work_rule_name: string | null; start_date: string; end_date: string | null }[]
}

export default function Edit({ user, workRules, currentWorkRule, histories }: Props) {
  const flash = usePage<any>().props.flash

  const { data, setData, patch, processing, errors } = useForm({
    work_rule_id: currentWorkRule?.id ? String(currentWorkRule.id) : '',
    start_date: new Date().toISOString().slice(0, 10), // YYYY-MM-DD
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    patch(route('admin.users.work-rule.update', user.id))
  }

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">ユーザー編集（勤務ルール割当）</h2>}>
      <Head title="ユーザー編集" />

      <div className="py-12">
        <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
          <div className="mb-4">
            <Link className="text-sm text-blue-600 hover:underline" href={route('admin.users.index')}>
              ← 一覧へ戻る
            </Link>
          </div>

          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 space-y-6">

              {flash?.success && <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
              {flash?.error && <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

              <div className="space-y-1">
                <div className="text-sm text-gray-700">ID: {user.id}</div>
                <div className="text-sm text-gray-700">名前: {user.name}</div>
                <div className="text-sm text-gray-700">Email: {user.email}</div>
                <div className="text-sm text-gray-700">Role: {user.role}</div>
              </div>

              <div className="rounded-md bg-gray-50 p-4">
                <div className="text-sm text-gray-700">
                  現在の勤務ルール：{currentWorkRule ? currentWorkRule.name : '未設定'}
                </div>
              </div>

              <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label className="block text-xs text-gray-600">勤務ルール</label>
                    <select
                      value={data.work_rule_id}
                      onChange={(e) => setData('work_rule_id', e.target.value)}
                      className="mt-1 w-full rounded-md border-gray-300 text-sm"
                    >
                      <option value="">選択してください</option>
                      {workRules.map((r) => (
                        <option key={r.id} value={String(r.id)}>
                          {r.name}
                        </option>
                      ))}
                    </select>
                    {errors.work_rule_id && <div className="mt-1 text-xs text-red-600">{errors.work_rule_id}</div>}
                  </div>

                  <div>
                    <label className="block text-xs text-gray-600">適用開始日</label>
                    <input
                      type="date"
                      value={data.start_date}
                      onChange={(e) => setData('start_date', e.target.value)}
                      className="mt-1 w-full rounded-md border-gray-300 text-sm"
                    />
                    {errors.start_date && <div className="mt-1 text-xs text-red-600">{errors.start_date}</div>}
                  </div>
                </div>

                <button type="submit" disabled={processing} className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40">
                  割り当て
                </button>

                <div className="text-xs text-gray-500">
                  ※ 開始日以降に履歴がある場合は安全のため更新を拒否します（最小版）。
                </div>
              </form>

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
                    {histories.map(h => (
                      <tr key={h.id} className="border-t">
                        <td className="py-2">{h.work_rule_name ?? `#${h.work_rule_id}`}</td>
                        <td>{h.start_date}</td>
                        <td>{h.end_date ?? '—'}</td>
                      </tr>
                    ))}
                    {histories.length === 0 && (
                      <tr className="border-t">
                        <td className="py-2 text-gray-500" colSpan={3}>履歴がありません</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}