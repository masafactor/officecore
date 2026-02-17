import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router, usePage } from '@inertiajs/react'
import { useMemo, useState } from 'react'

type Link = { url: string | null; label: string; active: boolean }

type CorrectionRow = {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  reason: string | null
  note: string | null
  clock_in_at: string | null // "YYYY-MM-DD HH:MM:SS"
  clock_out_at: string | null
  created_at: string | null

  attendance: {
    id: number
    work_date: string | null // "YYYY-MM-DD"
    clock_in: string | null  // "HH:MM"
    clock_out: string | null // "HH:MM"
    is_next_day: boolean      // 元が翌日かどうか（表示用）
  } | null

  user: { id: number; name: string; email: string } | null
  requester: { id: number; name: string } | null
}

type Props = {
  corrections: {
    data: CorrectionRow[]
    links: Link[]
    total: number
  }
}

const fmtYmd = (ymd: string | null) => (ymd ? ymd.replaceAll('-', '/') : '')
const fmtTime = (t: string | null) => t ?? ''
const toHHmm = (datetime: string | null) => (datetime ? datetime.slice(11, 16) : null)
const toYmd = (datetime: string | null) => (datetime ? datetime.slice(0, 10) : null)

const fmtDateTime = (s: string | null) => {
  if (!s) return '—'
  try {
    const iso = s.includes('T') ? s : s.replace(' ', 'T')
    return new Date(iso).toLocaleString('ja-JP')
  } catch {
    return s
  }
}

const fmtClockOut = (clockOut: string | null, isNextDay: boolean | undefined) => {
  if (!clockOut) return ''
  return isNextDay ? `${clockOut}（翌日）` : clockOut
}

// 申請（after）が翌日か判定：work_date と clock_out_at の日付差
const isNextDayFromRequest = (workDate: string | null, clockOutAt: string | null) => {
  if (!workDate || !clockOutAt) return false
  const reqYmd = toYmd(clockOutAt)
  return !!reqYmd && reqYmd !== workDate
}

const statusLabel = (s: CorrectionRow['status']) => (s === 'pending' ? '未処理' : s === 'approved' ? '承認' : '却下')
const statusClass = (s: CorrectionRow['status']) =>
  s === 'pending'
    ? 'text-yellow-700 bg-yellow-50 border-yellow-200'
    : s === 'approved'
      ? 'text-green-700 bg-green-50 border-green-200'
      : 'text-red-700 bg-red-50 border-red-200'

export default function Index({ corrections }: Props) {
  const flash = usePage<any>().props.flash
  const rows = corrections?.data ?? []
  const pendingCount = useMemo(() => rows.filter((r) => r?.status === 'pending').length, [rows])

  const [editingId, setEditingId] = useState<number | null>(null)

  // 反映する値（draft）
  const [clockInDraft, setClockInDraft] = useState<string>('')   // "HH:MM"
  const [clockOutDraft, setClockOutDraft] = useState<string>('') // "HH:MM"
  const [clockOutNextDayDraft, setClockOutNextDayDraft] = useState<boolean>(false) // ✅ 追加
  const [noteDraft, setNoteDraft] = useState<string>('')

  // ✅ 修正開始：反映する値を「申請値→元値」の順で読み込む
  const startEdit = (row: CorrectionRow) => {
    const a = row.attendance
    const workDate = a?.work_date ?? null

    const cinAfter = toHHmm(row.clock_in_at) // 申請
    const coutAfter = toHHmm(row.clock_out_at)
    const coutAfterNextDay = isNextDayFromRequest(workDate, row.clock_out_at)

    setEditingId(row.id)

    // 出勤：申請値があればそれ、なければ元
    setClockInDraft(cinAfter ?? a?.clock_in ?? '')

    // 退勤：申請値があればそれ、なければ元
    setClockOutDraft(coutAfter ?? a?.clock_out ?? '')

    // 退勤の翌日：申請があれば申請基準、なければ元のフラグ
    setClockOutNextDayDraft(row.clock_out_at ? coutAfterNextDay : !!a?.is_next_day)

    setNoteDraft(row.note ?? '')
  }

  const cancelEdit = () => {
    setEditingId(null)
    setClockInDraft('')
    setClockOutDraft('')
    setClockOutNextDayDraft(false)
    setNoteDraft('')
  }

  const approve = (row: CorrectionRow) => {
    const payload: Record<string, any> = {}

    // 編集中のときだけ、上書き値を送る
    if (editingId === row.id) {
      // 空なら送らない（=申請値のまま反映）
      if (clockInDraft) payload.clock_in = clockInDraft
      if (clockOutDraft) payload.clock_out = clockOutDraft

      // ✅ 日跨ぎも一緒に送る（clock_out を送る時だけ送るのが安全）
      if (clockOutDraft) payload.clock_out_is_next_day = clockOutNextDayDraft

      if (noteDraft !== (row.note ?? '')) payload.note = noteDraft
    }

    router.post(route('admin.attendance-corrections.approve', row.id), payload, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => cancelEdit(),
    })
  }

  const reject = (id: number) => {
    router.post(route('admin.attendance-corrections.reject', id), {}, { preserveState: true, preserveScroll: true })
  }

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">勤怠 修正申請一覧（管理者）</h2>}>
      <Head title="勤怠 修正申請一覧（管理者）" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">
              {(flash?.success || flash?.error) && (
                <div className="space-y-2">
                  {flash?.success && <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                  {flash?.error && <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}
                </div>
              )}

              <div className="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <div>件数：{corrections?.total ?? 0}</div>
                <div className="rounded-md border px-2 py-1 text-xs">このページ未処理：{pendingCount}</div>
                <div className="ml-auto text-xs text-gray-500">URL：/admin/attendance-corrections</div>
              </div>

              <div className="overflow-x-auto">
                <table className="min-w-full border">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">状態</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">対象日</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">従業員</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メール</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">出勤</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">退勤</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">申請者</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">申請日時</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">操作</th>
                    </tr>
                  </thead>

                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td className="px-3 py-4 text-sm text-gray-600" colSpan={9}>
                          修正申請はありません。
                        </td>
                      </tr>
                    ) : (
                      rows.map((row) => {
                        const isEditing = editingId === row.id
                        const u = row.user
                        const a = row.attendance

                        const workDate = a?.work_date ?? null
                        const cinBefore = a?.clock_in ?? null
                        const coutBefore = a?.clock_out ?? null

                        // ✅ 申請（after）は AttendanceCorrection から取る：nullなら “—”
                        const cinAfter = toHHmm(row.clock_in_at)
                        const coutAfterHHmm = toHHmm(row.clock_out_at)
                        const coutAfterNextDay = isNextDayFromRequest(workDate, row.clock_out_at)
                        const coutAfter = coutAfterHHmm ? (coutAfterNextDay ? `${coutAfterHHmm}（翌日）` : coutAfterHHmm) : null

                        return (
                          <>
                            <tr key={row.id} className="align-top">
                              <td className="border px-3 py-2 text-sm">
                                <span className={`inline-flex items-center rounded-md border px-2 py-1 text-xs ${statusClass(row.status)}`}>
                                  {statusLabel(row.status)}
                                </span>
                              </td>

                              <td className="border px-3 py-2 text-sm">{fmtYmd(workDate)}</td>
                              <td className="border px-3 py-2 text-sm">{u?.name ?? '—'}</td>
                              <td className="border px-3 py-2 text-sm text-gray-600">{u?.email ?? '—'}</td>

                              <td className="border px-3 py-2 text-sm">
                                <div className="flex items-center gap-2">
                                  <span className="text-gray-700">{fmtTime(cinBefore)}</span>
                                  <span className="text-gray-400"></span>
                                  <span className="text-gray-900">{fmtTime(cinAfter)}</span>
                                </div>
                              </td>

                              <td className="border px-3 py-2 text-sm">
                                <div className="flex items-center gap-2">
                                  <span className="text-gray-700">{fmtClockOut(coutBefore, a?.is_next_day)}</span>
                                  
                                  <span className="text-gray-900">{fmtTime(coutAfter)}</span>
                                </div>
                              </td>

                              <td className="border px-3 py-2 text-sm text-gray-700">{row.requester?.name ?? '—'}</td>
                              <td className="border px-3 py-2 text-sm text-gray-600">{fmtDateTime(row.created_at)}</td>

                              <td className="border px-3 py-2 text-sm">
                                {row.status === 'pending' ? (
                                  isEditing ? (
                                    <div className="flex flex-col gap-2">
                                      <button type="button" onClick={() => approve(row)} className="rounded-md bg-gray-900 px-3 py-1.5 text-white">
                                        承認（反映）
                                      </button>
                                      <button type="button" onClick={cancelEdit} className="rounded-md border px-3 py-1.5">
                                        キャンセル
                                      </button>
                                    </div>
                                  ) : (
                                    <div className="flex flex-col gap-2">
                                      <button type="button" onClick={() => startEdit(row)} className="rounded-md border px-3 py-1.5">
                                        修正
                                      </button>
                                      <button type="button" onClick={() => approve(row)} className="rounded-md bg-gray-900 px-3 py-1.5 text-white">
                                        承認
                                      </button>
                                      <button type="button" onClick={() => reject(row.id)} className="rounded-md border px-3 py-1.5">
                                        却下
                                      </button>
                                    </div>
                                  )
                                ) : (
                                  <span className="text-gray-500"></span>
                                )}
                              </td>
                            </tr>

                            <tr key={`${row.id}-detail`} className="bg-gray-50/30">
                              <td className="border px-3 py-3 text-sm" colSpan={9}>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                  <div className="md:col-span-2">
                                    <div className="text-xs text-gray-500">理由</div>
                                    <div className="mt-1 whitespace-pre-wrap break-words text-gray-800">{row.reason?.trim() ? row.reason : '（理由なし）'}</div>
                                  </div>

                                  <div>
                                    <div className="text-xs text-gray-500">管理メモ（任意）</div>
                                    {!isEditing ? (
                                      <div className="mt-1 whitespace-pre-wrap break-words text-gray-800">{row.note?.trim() ? row.note : '（メモなし）'}</div>
                                    ) : (
                                      <textarea
                                        value={noteDraft}
                                        onChange={(e) => setNoteDraft(e.target.value)}
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm resize-y"
                                        rows={3}
                                        placeholder="管理メモ（任意）"
                                      />
                                    )}
                                  </div>
                                </div>

                                {isEditing && (
                                  <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                      <div className="text-xs text-gray-500">出勤（承認時に反映する値）</div>
                                      <input
                                        type="time"
                                        value={clockInDraft}
                                        onChange={(e) => setClockInDraft(e.target.value)}
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                      />
                                      <div className="mt-1 text-xs text-gray-400">空なら申請値のまま反映</div>
                                    </div>

                                    <div>
                                      <div className="text-xs text-gray-500">退勤（承認時に反映する値）</div>
                                      <input
                                        type="time"
                                        value={clockOutDraft}
                                        onChange={(e) => setClockOutDraft(e.target.value)}
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                      />

                                      {/* ✅ 日跨ぎ：日付を出さずに操作できる */}
                                      <label className="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                        <input
                                          type="checkbox"
                                          checked={clockOutNextDayDraft}
                                          onChange={(e) => setClockOutNextDayDraft(e.target.checked)}
                                        />
                                        退勤は翌日
                                      </label>

                                      <div className="mt-1 text-xs text-gray-400">空なら申請値のまま反映</div>
                                    </div>

                                    <div className="text-xs text-gray-500">※「承認（反映）」でこの入力値を使って反映します。</div>
                                  </div>
                                )}
                              </td>
                            </tr>
                          </>
                        )
                      })
                    )}
                  </tbody>
                </table>
              </div>

              <nav className="flex flex-wrap gap-2">
                {(corrections?.links ?? []).map((l, idx) => (
                  <button
                    key={idx}
                    type="button"
                    disabled={!l.url}
                    onClick={() => l.url && router.visit(l.url)}
                    className={`rounded border px-3 py-1 text-sm ${l.active ? 'bg-gray-900 text-white' : 'bg-white'} disabled:opacity-40`}
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
