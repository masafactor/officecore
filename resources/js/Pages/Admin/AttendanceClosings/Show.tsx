import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'

type ClosingStatus = 'draft' | 'submitted' | 'approved'

type AttendanceRow = {
  id: number
  work_date: string
  clock_in: string | null
  clock_out: string | null
  note: string | null
  is_late: boolean
  is_early_leave: boolean
  minutes: {
    scheduled_work_minutes: number
    total_work_minutes: number
    overtime_in: number
    overtime_out: number
    night: number
  }
}

type Props = {
  user: {
    id: number
    name: string
  }
  year: number
  month: number
  closing: {
    id: number
    status: ClosingStatus
    submitted_at: string | null
    approved_at: string | null
    approved_by: number | null
    approved_by_name: string | null
  } | null
  summary: {
    attendance_days: number
    late_count: number
    early_leave_count: number
    scheduled_work_minutes: number
    total_work_minutes: number
    overtime_in_minutes: number
    overtime_out_minutes: number
    night_minutes: number
  }
  attendances: AttendanceRow[]
}

function formatMinutes(minutes: number) {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}時間${m}分`
}

function statusLabel(status?: ClosingStatus) {
  switch (status) {
    case 'draft':
      return '下書き'
    case 'submitted':
      return '提出済み'
    case 'approved':
      return '承認済み'
    default:
      return '未申請'
  }
}

export default function Show({
  user,
  year,
  month,
  closing,
  summary,
  attendances,
}: Props) {
  const handleApprove = () => {
    router.post(route('admin.attendance.closing.approve'), {
      user_id: user.id,
      year,
      month,
    })
  }

  const handleUnapprove = () => {
    router.post(route('admin.attendance.closing.unapprove'), {
      user_id: user.id,
      year,
      month,
    })
  }

  return (
    <AuthenticatedLayout>
      <Head title="月次申請詳細" />

      <div className="mx-auto max-w-7xl p-6">
        <div className="mb-6 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">
              月次申請詳細
            </h1>
            <p className="mt-1 text-sm text-gray-600">
              {user.name} / {year}年{month}月
            </p>
          </div>

          <Link
            href={route('admin.attendance.closings.index', { year, month })}
            className="rounded border px-4 py-2 text-sm hover:bg-gray-50"
          >
            一覧へ戻る
          </Link>
        </div>

        <div className="mb-6 rounded bg-white p-4 shadow">
          <div className="grid gap-4 md:grid-cols-4">
            <div>
              <p className="text-sm text-gray-500">状態</p>
              <p className="font-semibold">{statusLabel(closing?.status)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">提出日時</p>
              <p>{closing?.submitted_at ?? '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">承認日時</p>
              <p>{closing?.approved_at ?? '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">承認者</p>
              <p>{closing?.approved_by_name ?? '-'}</p>
            </div>
          </div>

          <div className="mt-4 flex gap-2">
            {closing?.status === 'submitted' && (
              <button
                type="button"
                onClick={handleApprove}
                className="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
              >
                承認
              </button>
            )}

            {closing?.status === 'approved' && (
              <button
                type="button"
                onClick={handleUnapprove}
                className="rounded bg-yellow-600 px-4 py-2 text-white hover:bg-yellow-700"
              >
                承認解除
              </button>
            )}
          </div>
        </div>

        <div className="mb-6 rounded bg-white p-4 shadow">
          <h2 className="mb-4 text-lg font-bold">月次サマリー</h2>

          <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
            <div>
              <p className="text-sm text-gray-500">出勤日数</p>
              <p className="font-semibold">{summary.attendance_days}日</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">遅刻回数</p>
              <p className="font-semibold">{summary.late_count}回</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">早退回数</p>
              <p className="font-semibold">{summary.early_leave_count}回</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">所定労働時間</p>
              <p className="font-semibold">{formatMinutes(summary.scheduled_work_minutes)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">実労働時間</p>
              <p className="font-semibold">{formatMinutes(summary.total_work_minutes)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">所定内残業</p>
              <p className="font-semibold">{formatMinutes(summary.overtime_in_minutes)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">所定外残業</p>
              <p className="font-semibold">{formatMinutes(summary.overtime_out_minutes)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">深夜時間</p>
              <p className="font-semibold">{formatMinutes(summary.night_minutes)}</p>
            </div>
          </div>
        </div>

        <div className="rounded bg-white p-4 shadow">
          <h2 className="mb-4 text-lg font-bold">勤務一覧</h2>

          <div className="overflow-x-auto">
            <table className="min-w-full border-collapse">
              <thead>
                <tr className="bg-gray-100 text-left">
                  <th className="border px-4 py-2">日付</th>
                  <th className="border px-4 py-2">出勤</th>
                  <th className="border px-4 py-2">退勤</th>
                  <th className="border px-4 py-2">実労働</th>
                  <th className="border px-4 py-2">所定内残業</th>
                  <th className="border px-4 py-2">所定外残業</th>
                  <th className="border px-4 py-2">深夜</th>
                  <th className="border px-4 py-2">遅刻</th>
                  <th className="border px-4 py-2">早退</th>
                  <th className="border px-4 py-2">備考</th>
                </tr>
              </thead>
              <tbody>
                {attendances.length === 0 ? (
                  <tr>
                    <td colSpan={10} className="border px-4 py-6 text-center text-gray-500">
                      勤務データがありません
                    </td>
                  </tr>
                ) : (
                  attendances.map((row) => (
                    <tr key={row.id}>
                      <td className="border px-4 py-2">{row.work_date}</td>
                      <td className="border px-4 py-2">{row.clock_in ?? '-'}</td>
                      <td className="border px-4 py-2">{row.clock_out ?? '-'}</td>
                      <td className="border px-4 py-2">{formatMinutes(row.minutes.total_work_minutes)}</td>
                      <td className="border px-4 py-2">{formatMinutes(row.minutes.overtime_in)}</td>
                      <td className="border px-4 py-2">{formatMinutes(row.minutes.overtime_out)}</td>
                      <td className="border px-4 py-2">{formatMinutes(row.minutes.night)}</td>
                      <td className="border px-4 py-2">{row.is_late ? '◯' : '-'}</td>
                      <td className="border px-4 py-2">{row.is_early_leave ? '◯' : '-'}</td>
                      <td className="border px-4 py-2">{row.note ?? '-'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}