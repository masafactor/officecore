import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router, usePage } from '@inertiajs/react'
import { useMemo, useState } from 'react'

type Link = { url: string | null; label: string; active: boolean }

type CorrectionRow = {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  reason: string | null
  note: string | null
  clock_in_at: string | null // "YYYY-MM-DD HH:MM:SS"（コントローラの toDateTimeString）
  clock_out_at: string | null
  created_at: string | null

  attendance: {
    id: number
    work_date: string | null // "YYYY-MM-DD"
    clock_in: string | null // "HH:MM"
    clock_out: string | null // "HH:MM"
  }

  user: {
    id: number
    name: string
    email: string
  }

  requester: {
    id: number
    name: string
  }
}

type Props = {
  auth: { user: { name: string } }
  corrections: {
    data: CorrectionRow[]
    links: Link[]
    total: number
  }
  // 今は filters を渡してない想定（必要なら後で追加）
}

const fmtTime = (t: string | null) => t ?? '—'
const fmtYmd = (ymd: string | null) => (ymd ? ymd.replaceAll('-', '/') : '—')

const fmtDateTime = (s: string | null) => {
  if (!s) return '—'
  try {
    // "2026-01-31 03:52:12" みたいな形式でも Date に通らないことがあるので保険
    const iso = s.includes('T') ? s : s.replace(' ', 'T')
    return new Date(iso).toLocaleString('ja-JP')
  } catch {
    return s
  }
}

const statusLabel = (s: CorrectionRow['status']) => {
  switch (s) {
    case 'pending':
      return '未処理'
    case 'approved':
      return '承認'
    case 'rejected':
      return '却下'
  }
}

const statusClass = (s: CorrectionRow['status']) => {
  switch (s) {
    case 'pending':
      return 'text-yellow-700 bg-yellow-50 border-yellow-200'
    case 'approved':
      return 'text-green-700 bg-green-50 border-green-200'
    case 'rejected':
      return 'text-red-700 bg-red-50 border-red-200'
  }
}

const changed = (before: string | null, after: string | null) => {
  return (before ?? '') !== (after ?? '')
}

export default function Index({ corrections }: Props) {
  const flash = usePage<any>().props.flash

  const rows = corrections?.data ?? []
  const pendingCount = useMemo(() => rows.filter((r) => r?.status === 'pending').length, [rows])

  // ここは後で実装。今は置きボタンだけ。
  const approve = (id: number) => alert(`approve: ${id}（後で実装）`)
  const reject = (id: number) => alert(`reject: ${id}（後で実装）`)

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">勤怠 修正申請一覧（管理者）</h2>}
    >
      <Head title="勤怠 修正申請一覧（管理者）" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">

              {/* flash */}
              {(flash?.success || flash?.error) && (
                <div className="space-y-2">
                  {flash?.success && (
                    <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">
                      {flash.success}
                    </div>
                  )}
                  {flash?.error && (
                    <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                      {flash.error}
                    </div>
                  )}
                </div>
              )}

              {/* header stats */}
              <div className="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <div>件数：{corrections?.total ?? 0}</div>
                <div className="rounded-md border px-2 py-1 text-xs">
                  このページ未処理：{pendingCount}
                </div>

                {/* 将来フィルタを付けたくなったらここにフォームを足す */}
                <div className="ml-auto text-xs text-gray-500">
                  URL：/admin/attendance-corrections
                </div>
              </div>

              {/* table */}
              <div className="overflow-x-auto">
                <table className="min-w-full border">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">状態</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">対象日</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">従業員</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">メール</th>

                      <th className="border px-3 py-2 text-left text-xs text-gray-600">出勤（元→申請）</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">退勤（元→申請）</th>

                      <th className="border px-3 py-2 text-left text-xs text-gray-600">理由</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">管理メモ</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">申請者</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">申請日時</th>
                      <th className="border px-3 py-2 text-left text-xs text-gray-600">操作</th>
                    </tr>
                  </thead>

                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td className="px-3 py-4 text-sm text-gray-600" colSpan={11}>
                          修正申請はありません。
                        </td>
                      </tr>
                    ) : (
                      rows.map((row) => {
                        // ガード（undefined混入でも落とさない）
                        if (!row) return null

                        const u = row.user
                        const a = row.attendance

                        const workDate = a?.work_date ?? null

                        // 元データ
                        const cinBefore = a?.clock_in ?? null
                        const coutBefore = a?.clock_out ?? null

                        // 申請データ（コントローラの clock_in_at/out_at を "HH:MM" 表示にする）
                        const cinAfter = row.clock_in_at ? row.clock_in_at.slice(11, 16) : null
                        const coutAfter = row.clock_out_at ? row.clock_out_at.slice(11, 16) : null

                        const cinChanged = changed(cinBefore, cinAfter)
                        const coutChanged = changed(coutBefore, coutAfter)

                        return (
                          <tr key={row.id} className="align-top">
                            {/* status */}
                            <td className="border px-3 py-2 text-sm">
                              <span className={`inline-flex items-center rounded-md border px-2 py-1 text-xs ${statusClass(row.status)}`}>
                                {statusLabel(row.status)}
                              </span>
                            </td>

                            {/* date */}
                            <td className="border px-3 py-2 text-sm">{fmtYmd(workDate)}</td>

                            {/* user */}
                            <td className="border px-3 py-2 text-sm">{u?.name ?? '—'}</td>
                            <td className="border px-3 py-2 text-sm text-gray-600">{u?.email ?? '—'}</td>

                            {/* clock in */}
                            <td className="border px-3 py-2 text-sm">
                              <div className="flex items-center gap-2">
                                <span className="text-gray-700">{fmtTime(cinBefore)}</span>
                                <span className="text-gray-400">→</span>
                                <span className={cinChanged ? 'font-semibold text-gray-900' : 'text-gray-700'}>
                                  {fmtTime(cinAfter)}
                                </span>
                                {cinChanged && (
                                  <span className="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">
                                    変更
                                  </span>
                                )}
                              </div>
                            </td>

                            {/* clock out */}
                            <td className="border px-3 py-2 text-sm">
                              <div className="flex items-center gap-2">
                                <span className="text-gray-700">{fmtTime(coutBefore)}</span>
                                <span className="text-gray-400">→</span>
                                <span className={coutChanged ? 'font-semibold text-gray-900' : 'text-gray-700'}>
                                  {fmtTime(coutAfter)}
                                </span>
                                {coutChanged && (
                                  <span className="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">
                                    変更
                                  </span>
                                )}
                              </div>
                            </td>

                            {/* reason */}
                            <td className="border px-3 py-2 text-sm">
                              <div className="max-w-[18rem] whitespace-pre-wrap break-words text-gray-700">
                                {row.reason?.trim() ? row.reason : '（理由なし）'}
                              </div>
                            </td>

                            {/* note */}
                            <td className="border px-3 py-2 text-sm">
                              <div className="max-w-[18rem] whitespace-pre-wrap break-words text-gray-700">
                                {row.note?.trim() ? row.note : '（メモなし）'}
                              </div>
                            </td>

                            {/* requester */}
                            <td className="border px-3 py-2 text-sm text-gray-700">
                              {row.requester?.name ?? '—'}
                            </td>

                            {/* created */}
                            <td className="border px-3 py-2 text-sm text-gray-600">
                              {fmtDateTime(row.created_at)}
                            </td>

                            {/* actions */}
                            <td className="border px-3 py-2 text-sm">
                              {row.status === 'pending' ? (
                                <div className="flex flex-col gap-2">
                                  <button
                                    type="button"
                                    onClick={() => approve(row.id)}
                                    className="rounded-md bg-gray-900 px-3 py-1.5 text-white"
                                  >
                                    承認
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => reject(row.id)}
                                    className="rounded-md border px-3 py-1.5"
                                  >
                                    却下
                                  </button>
                                </div>
                              ) : (
                                <span className="text-gray-500">—</span>
                              )}
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
                {(corrections?.links ?? []).map((l, idx) => (
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
