import AttendanceCorrectionForm from '@/Components/AttendanceCorrectionForm'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { useState } from 'react'

type AttendanceRow = {
  id: number
  work_date: string
  clock_in: string | null
  clock_out: string | null
  note: string | null
  updated_at: string | null
  is_late: boolean
  is_early_leave: boolean
}

type Link = { url: string | null; label: string; active: boolean }

type Props = {
  filters: { month: string }
  attendances: {
    data: AttendanceRow[]
    links: Link[]
    total: number
  }
}

const fmt = (v: string | null) => v ?? '—'
const ymd = (s: string) => s.replaceAll('-', '/')

export default function Index({ filters, attendances }: Props) {
  const [month, setMonth] = useState(filters.month)
  const [targetAttendance, setTargetAttendance] = useState<AttendanceRow | null>(null)

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    router.get('/attendances', { month }, { preserveState: true })
  }

  const openCorrection = (row: AttendanceRow) => {
    setTargetAttendance(row)
  }

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">勤怠履歴</h2>}>
      <Head title="勤怠履歴" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">
              {/* filter */}
              <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                <div>
                  <label className="block text-xs text-gray-600">対象月</label>
                  <input
                    type="month"
                    value={month}
                    onChange={(e) => setMonth(e.target.value)}
                    className="mt-1 rounded-md border-gray-300 text-sm"
                  />
                </div>

                <button type="submit" className="rounded-md bg-gray-900 px-4 py-2 text-white">
                  表示
                </button>

                <div className="ml-auto text-sm text-gray-600">件数：{attendances.total}</div>
              </form>

              {/* table */}
              <div className="overflow-x-auto">
                <table className="min-w-full border">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">日付</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">出勤</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">退勤</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">状態</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メモ</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">操作</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">最終更新</th>
                    </tr>
                  </thead>

                  <tbody>
                    {attendances.data.length === 0 ? (
                      <tr>
                        <td className="px-3 py-4 text-sm text-gray-600" colSpan={7}>
                          この月の勤怠データはありません。
                        </td>
                      </tr>
                    ) : (
                      attendances.data.map((row) => {
                        const status = !row.clock_in ? '未出勤' : !row.clock_out ? '勤務中' : '退勤済'
                        const statusClass = !row.clock_in
                          ? 'text-red-600'
                          : !row.clock_out
                            ? 'text-blue-600'
                            : 'text-gray-700'

                        return (
                          <tr key={row.id}>
                            <td className="border px-3 py-2 text-sm">{ymd(row.work_date)}</td>
                            <td className="border px-3 py-2 text-sm">{fmt(row.clock_in)}</td>
                            <td className="border px-3 py-2 text-sm">{fmt(row.clock_out)}</td>

                            {/* 状態 + 遅刻/早退 */}
                            <td className="border px-3 py-2 text-sm">
                              <div className="flex flex-col gap-1">
                                <span className={statusClass}>{status}</span>
                                <div className="flex flex-wrap gap-1">
                                  {row.is_late && (
                                    <span className="inline-flex items-center rounded border border-red-200 bg-red-50 px-2 py-0.5 text-[11px] text-red-700">
                                      遅刻
                                    </span>
                                  )}
                                  {row.is_early_leave && (
                                    <span className="inline-flex items-center rounded border border-orange-200 bg-orange-50 px-2 py-0.5 text-[11px] text-orange-700">
                                      早退
                                    </span>
                                  )}
                                </div>
                              </div>
                            </td>

                            <td className="border px-3 py-2 text-sm text-gray-700">
                              {row.note?.trim() ? row.note : '—'}
                            </td>

                            {/* 操作 */}
                            <td className="border px-3 py-2 text-sm">
                              <button
                                type="button"
                                onClick={() => openCorrection(row)}
                                className="rounded-md border px-3 py-1 text-xs hover:bg-gray-50"
                              >
                                修正申請
                              </button>
                            </td>

                            <td className="border px-3 py-2 text-sm text-gray-600">
                              {row.updated_at ? new Date(row.updated_at).toLocaleString('ja-JP') : '—'}
                            </td>
                          </tr>
                        )
                      })
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

          {/* 修正申請フォーム（下に出す） */}
          {targetAttendance && (
            <div className="mt-6">
              <AttendanceCorrectionForm
                attendance={targetAttendance}
                today={targetAttendance.work_date}
                onClose={() => setTargetAttendance(null)}
              />
            </div>
          )}
        </div>
      </div>
    </AuthenticatedLayout>
  )
}