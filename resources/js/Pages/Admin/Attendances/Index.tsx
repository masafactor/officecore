import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { useState } from 'react'
import { usePage } from '@inertiajs/react'

type AttendanceRow = {
  id: number
  work_date: string
  clock_in: string | null
  clock_out: string | null
  note: string | null
  user: { id: number; name: string; email: string }
  worked_minutes: number | null
  updated_at: string | null
  overtime_minutes: number | null
  night_minutes: number | null

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

const fmtTime = (hhmm: string | null) => {
  return hhmm ?? '—'
}


const fmtMinutes = (m: number | null) => {
  if (m == null) return '—'
  const h = Math.floor(m / 60)
  const mm = m % 60
  return `${h}h ${mm}m`
}

export default function Index({ auth, filters, attendances }: Props) {
  const [date, setDate] = useState(filters.date ?? new Date().toISOString().slice(0, 10))

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    router.get('/admin/attendances', { date }, { preserveState: true })
  }

  const [editingId, setEditingId] = useState<number | null>(null)
  const [noteDraft, setNoteDraft] = useState<string>('')

  const [clockInDateDraft, setClockInDateDraft] = useState<string>('')
  const [clockOutDateDraft, setClockOutDateDraft] = useState<string>('')

const startEdit = (row: AttendanceRow) => {
  setEditingId(row.id)

  // 時刻は row が "H:i" で来てる前提
  setClockInDraft(row.clock_in ?? '')
  setClockOutDraft(row.clock_out ?? '')

  // まず同日にしておく
  setClockInDateDraft(row.work_date)
  setClockOutDateDraft(row.work_date)

  // 日跨ぎっぽい（退勤が出勤より小さい）なら退勤日を翌日に寄せる
  if (row.clock_in && row.clock_out && row.clock_out < row.clock_in) {
    setClockOutDateDraft(addDaysYMD(row.work_date, 1))
  }

  setNoteDraft(row.note ?? '')
}





  const flash = usePage<any>().props.flash


const [clockInDraft, setClockInDraft] = useState<string>('')   // "HH:MM" or ""
const [clockOutDraft, setClockOutDraft] = useState<string>('') // "HH:MM" or ""




const cancelEdit = () => {
  setEditingId(null)
  setNoteDraft('')
  setClockInDraft('')
  setClockOutDraft('')
  setClockInDateDraft('')
  setClockOutDateDraft('')
}


const todayYMD = () => new Date().toISOString().slice(0, 10)

const pad2 = (n: number) => String(n).padStart(2, '0')

const addDaysYMD = (ymd: string, days: number) => {
  const [y, m, d] = ymd.split('-').map(Number)
  const utc = new Date(Date.UTC(y, m - 1, d)) // UTCの0:00
  utc.setUTCDate(utc.getUTCDate() + days)

  const yy = utc.getUTCFullYear()
  const mm = pad2(utc.getUTCMonth() + 1)
  const dd = pad2(utc.getUTCDate())
  return `${yy}-${mm}-${dd}`
}


const save = (id: number) => {
  router.patch(
    `/admin/attendances/${id}`,
    {
      clock_in_date: clockInDateDraft || null,
      clock_in: clockInDraft || null,
      clock_out_date: clockOutDateDraft || null,
      clock_out: clockOutDraft || null,
      note: noteDraft || null,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => cancelEdit(),
    }
  )
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
                {flash?.success && (
                  <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">
                    {flash.success}
                  </div>
                )}

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
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">状態</th>
                      <th className="border px-3 py-2 text-xs">残業</th>
                      <th className="border px-3 py-2 text-xs">深夜</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">実働</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メモ</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">最終更新</th>
                    </tr>
                  </thead>

                  <tbody>
                    {attendances.data.length === 0 ? (
                      <tr>
                        <td className="px-3 py-4 text-sm text-gray-600" colSpan={7}>
                          この日の勤怠データはありません。
                        </td>
                      </tr>
                    ) : (
                      attendances.data.map((row) => {
                        const status = !row.clock_in
                          ? '未出勤'
                          : !row.clock_out
                          ? '勤務中'
                          : '退勤済'

                        const statusClass = !row.clock_in
                          ? 'text-red-600'
                          : !row.clock_out
                          ? 'text-blue-600'
                          : 'text-gray-600'

                        return (
                          <tr key={row.id}>
                            <td className="border px-3 py-2 text-sm">{row.user.name}</td>
                            <td className="border px-3 py-2 text-sm text-gray-600">{row.user.email}</td>
                            <td className="border px-3 py-2 text-sm">{fmtTime(row.clock_in)}</td>
                            <td className="border px-3 py-2 text-sm">{fmtTime(row.clock_out)}</td>
                            <td className="border px-3 py-2 text-sm">
                              <span className={statusClass}>{status}</span>
                            </td><td className="border px-3 py-2 text-sm">
                              {fmtMinutes(row.overtime_minutes ?? null)}
                            </td>
                            <td className="border px-3 py-2 text-sm">
                              {fmtMinutes(row.night_minutes ?? null)}
                            </td>
                            <td className="border px-3 py-2 text-sm">{fmtMinutes(row.worked_minutes)}</td>
                            <td className="border px-3 py-2 text-sm">
                              {editingId === row.id ? (
                                <div className="space-y-2">
                                  <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {/* 出勤 */}
                                    <div className="rounded-md border p-3">
                                      <div className="text-xs text-gray-500">出勤（修正）</div>
                                      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div>
                                          <div className="text-[11px] text-gray-500">日付</div>
                                          <input
                                            type="date"
                                            value={clockInDateDraft}
                                            onChange={(e) => setClockInDateDraft(e.target.value)}
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                          />
                                        </div>
                                        <div>
                                          <div className="text-[11px] text-gray-500">時刻</div>
                                          <input
                                            type="time"
                                            value={clockInDraft}
                                            onChange={(e) => setClockInDraft(e.target.value)}
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                          />
                                        </div>
                                      </div>
                                    </div>

                                    {/* 退勤 */}
                                    <div className="rounded-md border p-3">
                                      <div className="text-xs text-gray-500">退勤（修正）</div>
                                      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div>
                                          <div className="text-[11px] text-gray-500">日付</div>
                                          <input
                                            type="date"
                                            value={clockOutDateDraft}
                                            onChange={(e) => setClockOutDateDraft(e.target.value)}
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                          />
                                        </div>
                                        <div>
                                          <div className="text-[11px] text-gray-500">時刻</div>
                                          <input
                                            type="time"
                                            value={clockOutDraft}
                                            onChange={(e) => setClockOutDraft(e.target.value)}
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                          />
                                        </div>
                                      </div>

                                      <div className="mt-2 flex gap-2">
                                        <button
                                          type="button"
                                          onClick={() =>
                                            setClockOutDateDraft(addDaysYMD(clockOutDateDraft || row.work_date, 1))
                                          }
                                          className="rounded-md border px-2 py-1 text-xs"
                                        >
                                          退勤を翌日に
                                        </button>
                                        <button
                                          type="button"
                                          onClick={() => setClockOutDateDraft(row.work_date)}
                                          className="rounded-md border px-2 py-1 text-xs"
                                        >
                                          退勤を当日に戻す
                                        </button>
                                      </div>
                                    </div>
                                  </div>

                                  <div>
                                    <div className="text-xs text-gray-500">管理メモ</div>
                                    <textarea
                                      value={noteDraft}
                                      onChange={(e) => setNoteDraft(e.target.value)}
                                      className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                      rows={2}
                                    />
                                  </div>

                                  <div className="flex gap-2">
                                    <button
                                      type="button"
                                      onClick={() => save(row.id)}
                                      className="rounded-md bg-gray-900 px-3 py-1.5 text-white"
                                    >
                                      保存
                                    </button>
                                    <button
                                      type="button"
                                      onClick={cancelEdit}
                                      className="rounded-md border px-3 py-1.5"
                                    >
                                      キャンセル
                                    </button>
                                  </div>
                                </div>
                              ) : (
                                <div className="flex items-center justify-between gap-3">
                                  <span className="text-gray-700">
                                    {row.note?.trim() ? row.note : '（管理メモなし）'}
                                  </span>
                                  <button
                                    type="button"
                                    onClick={() => startEdit(row)}
                                    className="rounded-md border px-2 py-1 text-xs"
                                  >
                                    編集
                                  </button>
                                </div>
                              )}
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
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
