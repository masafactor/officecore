import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { useState } from 'react'

type Row = {
  user: { id: number; name: string; email: string }
  clock_in_days: number
  clock_out_days: number
  worked_days: number
  worked_minutes_sum: number
  worked_minutes_avg: number | null
}

type Props = {
  auth: { user: { name: string } }
  filters: { month: string }
  rows: Row[]
}

const fmtMinutes = (m: number | null) => {
  if (m == null) return '—'
  const h = Math.floor(m / 60)
  const mm = m % 60
  return `${h}h ${mm}m`
}

export default function Monthly({ auth, filters, rows }: Props) {
  const [month, setMonth] = useState(filters.month ?? new Date().toISOString().slice(0, 7))

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    router.get('/admin/reports/monthly', { month }, { preserveState: true })
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">月次集計（管理者）</h2>}
    >
      <Head title="月次集計（管理者）" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">

              <form onSubmit={submit} className="flex items-end gap-3">
                <div>
                  <label className="block text-xs text-gray-600">対象月</label>
                  <input
                    type="month"
                    value={month}
                    onChange={(e) => setMonth(e.target.value)}
                    className="mt-1 rounded-md border-gray-300"
                  />
                </div>

                <button type="submit" className="rounded-md bg-gray-900 px-4 py-2 text-white">
                  表示
                </button>

                <div className="ml-auto text-sm text-gray-600">人数：{rows.length}</div>
              </form>

              <div className="overflow-x-auto">
                <table className="min-w-full border">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">氏名</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メール</th>
                      <th className="border px-3 py-2 text-right text-xs text-gray-600">出勤日数</th>
                      <th className="border px-3 py-2 text-right text-xs text-gray-600">退勤日数</th>
                      <th className="border px-3 py-2 text-right text-xs text-gray-600">実働日数</th>
                      <th className="border px-3 py-2 text-right text-xs text-gray-600">合計実働</th>
                      <th className="border px-3 py-2 text-right text-xs text-gray-600">平均実働</th>
                    </tr>
                  </thead>

                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td className="px-3 py-4 text-sm text-gray-600" colSpan={7}>
                          対象月のデータがありません。
                        </td>
                      </tr>
                    ) : (
                      rows.map((r) => (
                        <tr key={r.user.id}>
                          <td className="border px-3 py-2 text-sm">{r.user.name}</td>
                          <td className="border px-3 py-2 text-sm text-gray-600">{r.user.email}</td>
                          <td className="border px-3 py-2 text-sm text-right">{r.clock_in_days}</td>
                          <td className="border px-3 py-2 text-sm text-right">{r.clock_out_days}</td>
                          <td className="border px-3 py-2 text-sm text-right">{r.worked_days}</td>
                          <td className="border px-3 py-2 text-sm text-right">
                            {fmtMinutes(r.worked_minutes_sum)}
                          </td>
                          <td className="border px-3 py-2 text-sm text-right">
                            {fmtMinutes(r.worked_minutes_avg)}
                          </td>
                        </tr>
                      ))
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
