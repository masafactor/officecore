import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { FormEvent, useState } from 'react'

type ClosingStatus = 'draft' | 'submitted' | 'approved'

type ClosingRow = {
  id: number
  user_id: number
  user_name: string
  year: number
  month: number
  status: ClosingStatus
  submitted_at: string | null
  approved_at: string | null
  approved_by: number | null
  approved_by_name: string | null
}

type Props = {
  filters: {
    year: number
    month: number
  }
  closings: ClosingRow[]
}

function statusLabel(status: ClosingStatus) {
  switch (status) {
    case 'draft':
      return '下書き'
    case 'submitted':
      return '提出済み'
    case 'approved':
      return '承認済み'
    default:
      return status
  }
}

export default function Index({ filters, closings }: Props) {
  const [year, setYear] = useState(String(filters.year))
  const [month, setMonth] = useState(String(filters.month))
  const { flash } = usePage().props as {
    flash?: { success?: string; error?: string }
  }

  const handleSearch = (e: FormEvent) => {
    e.preventDefault()

    router.get(
      route('admin.attendance.closings.index'),
      { year, month },
      { preserveState: true, replace: true }
    )
  }

  const handleApprove = (row: ClosingRow) => {
    router.post(
      route('admin.attendance.closing.approve'),
      {
        user_id: row.user_id,
        year: row.year,
        month: row.month,
      },
      {
        preserveScroll: true,
      }
    )
  }

  const handleUnapprove = (row: ClosingRow) => {
    router.post(
      route('admin.attendance.closing.unapprove'),
      {
        user_id: row.user_id,
        year: row.year,
        month: row.month,
      },
      {
        preserveScroll: true,
      }
    )
  }

  return (
    <AuthenticatedLayout>
      <Head title="月次申請管理画面" />

      <div className="max-w-7xl mx-auto p-6">
        <h1 className="text-2xl font-bold mb-6">月次申請管理画面</h1>

        {flash?.success && (
          <div className="mb-4 rounded bg-green-100 px-4 py-3 text-green-800">
            {flash.success}
          </div>
        )}

        {flash?.error && (
          <div className="mb-4 rounded bg-red-100 px-4 py-3 text-red-800">
            {flash.error}
          </div>
        )}

        <form onSubmit={handleSearch} className="mb-6 flex items-end gap-4">
          <div>
            <label className="block text-sm mb-1">年</label>
            <input
              type="number"
              value={year}
              onChange={(e) => setYear(e.target.value)}
              className="border rounded px-3 py-2 w-32"
            />
          </div>

          <div>
            <label className="block text-sm mb-1">月</label>
            <input
              type="number"
              min={1}
              max={12}
              value={month}
              onChange={(e) => setMonth(e.target.value)}
              className="border rounded px-3 py-2 w-24"
            />
          </div>

          <button
            type="submit"
            className="rounded bg-blue-600 text-white px-4 py-2 hover:bg-blue-700"
          >
            検索
          </button>
        </form>

        <div className="overflow-x-auto bg-white shadow rounded">
          <table className="min-w-full border-collapse">
            <thead>
              <tr className="bg-gray-100 text-left">
                <th className="border px-4 py-2">ユーザーID</th>
                <th className="border px-4 py-2">氏名</th>
                <th className="border px-4 py-2">対象年月</th>
                <th className="border px-4 py-2">状態</th>
                <th className="border px-4 py-2">提出日時</th>
                <th className="border px-4 py-2">承認日時</th>
                <th className="border px-4 py-2">承認者</th>
                <th className="border px-4 py-2">操作</th>
              </tr>
            </thead>
            <tbody>
              {closings.length === 0 ? (
                <tr>
                  <td colSpan={8} className="border px-4 py-6 text-center text-gray-500">
                    データがありません
                  </td>
                </tr>
              ) : (
                closings.map((row) => (
                  <tr key={row.id}>
                    <td className="border px-4 py-2">{row.user_id}</td>
                    <td className="border px-4 py-2">{row.user_name}</td>
                    <td className="border px-4 py-2">
                      {row.year}年{row.month}月
                    </td>
                    <td className="border px-4 py-2">{statusLabel(row.status)}</td>
                    <td className="border px-4 py-2">{row.submitted_at ?? '-'}</td>
                    <td className="border px-4 py-2">{row.approved_at ?? '-'}</td>
                    <td className="border px-4 py-2">{row.approved_by_name ?? '-'}</td>
                    <td className="border px-4 py-2">
                      <div className="flex gap-2">
                        {row.status === 'submitted' && (
                          <button
                            type="button"
                            onClick={() => handleApprove(row)}
                            className="rounded bg-green-600 text-white px-3 py-1 hover:bg-green-700"
                          >
                            承認
                          </button>
                        )}

                        {row.status === 'approved' && (
                          <button
                            type="button"
                            onClick={() => handleUnapprove(row)}
                            className="rounded bg-yellow-600 text-white px-3 py-1 hover:bg-yellow-700"
                          >
                            承認解除
                          </button>
                        )}

                        {row.status === 'draft' && (
                          <span className="text-gray-400">操作なし</span>
                        )}

                        <Link
                        href={route('admin.attendance.closings.show', {
                          user: row.user_id,
                          year: row.year,
                          month: row.month,
                        })}
                        className="rounded bg-slate-600 px-3 py-1 text-white hover:bg-slate-700"
                      >
                        詳細
                      </Link>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}