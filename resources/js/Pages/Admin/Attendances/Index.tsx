import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { useState } from 'react'

type AttendanceRow = {
  id: number
  work_date: string
  clock_in: string | null
  clock_out: string | null
  note: string | null
  user: { id: number; name: string; email: string }
}

type Link = { url: string | null; label: string; active: boolean }

type Props = {
  auth: { user: { name: string } }
  filters: { date: string }
  attendances: {
    data: AttendanceRow[]
    links: Link[]
    total: number
  }
}

const fmtTime = (iso: string | null) => {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })
}

export default function Index({ auth, filters, attendances }: Props) {
  const [date, setDate] = useState(filters.date)

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    router.get('/admin/attendances', { date }, { preserveState: true })
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">勤怠一覧（管理者）</h2>}
    >
      <Head title="勤怠一覧（管理者）" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">

              <form onSubmit={submit} className="flex items-end gap-3">
                <div>
                  <label className="block text-xs text-gray-600">日付</label>
                  <input
                    type="date"
                    value={date}
                    onChange={(e) => setDate(e.target.value)}
                    className="mt-1 rounded-md border-gray-300"
                  />
                </div>
                <button
                  type="submit"
                  className="rounded-md bg-gray-900 px-4 py-2 text-white"
                >
                  表示
                </button>

                <div className="ml-auto text-sm text-gray-600">
                  件数：{attendances.total}
                </div>
              </form>

              <div className="overflow-x-auto">
                <table className="min-w-full border">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">氏名</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メール</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">出勤</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">退勤</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メモ</th>
                    </tr>
                  </thead>
                  <tbody>
                    {attendances.data.length === 0 ? (
                      <tr>
                        <td className="px-3 py-4 text-sm text-gray-600" colSpan={5}>
                          この日の勤怠データはありません。
                        </td>
                      </tr>
                    ) : (
                      attendances.data.map((row) => (
                        <tr key={row.id}>
                          <td className="border px-3 py-2 text-sm">{row.user.name}</td>
                          <td className="border px-3 py-2 text-sm text-gray-600">{row.user.email}</td>
                          <td className="border px-3 py-2 text-sm">{fmtTime(row.clock_in)}</td>
                          <td className="border px-3 py-2 text-sm">{fmtTime(row.clock_out)}</td>
                          <td className="border px-3 py-2 text-sm">{row.note ?? '—'}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>

              {/* pagination */}
              <nav className="flex flex-wrap gap-2">
                {attendances.links.map((l, idx) => (
                  <button
                    key={idx}
                    type="button"
                    disabled={!l.url}
                    onClick={() => l.url && router.visit(l.url)}
                    className={`rounded border px-3 py-1 text-sm ${
                      l.active ? 'bg-gray-900 text-white' : 'bg-white'
                    } disabled:opacity-40`}
                    dangerouslySetInnerHTML={{ __html: l.label }}
                  />
                ))}
              </nav>

            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
