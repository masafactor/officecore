import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import AttendanceCorrectionForm from '@/Components/AttendanceCorrectionForm'


type Attendance = {
  id: number
  work_date: string
  clock_in: string | null
  clock_out: string | null
}

type Props = {
  auth: { user: { name: string } }
  today: string
  attendance: Attendance | null
  workedMinutes: number | null
}

export default function Dashboard({ auth, today, attendance, workedMinutes }: Props) {
  const hasClockIn = !!attendance?.clock_in
  const hasClockOut = !!attendance?.clock_out

  const clockIn = () => {
    router.post(route('attendance.clockIn'))
  }

  const clockOut = () => {
    router.post(route('attendance.clockOut'))
  }

const fmtTime = (hhmm: string | null) => hhmm ?? '—'


  const fmtMinutes = (m: number | null) => {
    if (m == null) return '—'
    const h = Math.floor(m / 60)
    const mm = m % 60
    return `${h}h ${mm}m`
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>}
    >
      <Head title="Dashboard" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">

              {/* 今日の勤怠 */}
              <section className="space-y-2">
                <h3 className="text-lg font-semibold">今日の勤怠</h3>
                <div className="text-sm text-gray-600">日付：{today}</div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                  <div className="rounded-lg border p-4">
                    <div className="text-xs text-gray-500">出勤</div>
                    <div className="text-xl font-semibold">{fmtTime(attendance?.clock_in ?? null)}</div>
                  </div>

                  <div className="rounded-lg border p-4">
                    <div className="text-xs text-gray-500">退勤</div>
                    <div className="text-xl font-semibold">{fmtTime(attendance?.clock_out ?? null)}</div>
                  </div>

                  <div className="rounded-lg border p-4">
                    <div className="text-xs text-gray-500">実働</div>
                    <div className="text-xl font-semibold">{fmtMinutes(workedMinutes)}</div>
                    <div className="text-xs text-gray-500 mt-1">（固定休憩を差し引き）</div>
                  </div>

                  <div className="rounded-lg border p-4">
                    <div className="text-xs text-gray-500">状態</div>
                    <div className="text-xl font-semibold">
                      {!hasClockIn ? '未出勤' : !hasClockOut ? '勤務中' : '退勤済'}
                    </div>
                  </div>
                </div>

                <div className="flex gap-3 pt-2">
                  <button
                    type="button"
                    onClick={clockIn}
                    disabled={hasClockIn}
                    className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
                  >
                    出勤
                  </button>

                  <button
                    type="button"
                    onClick={clockOut}
                    disabled={!hasClockIn || hasClockOut}
                    className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
                  >
                    退勤
                  </button>
                </div>

                {/* フラッシュメッセージ（任意） */}
                {/* Breeze標準の flash 表示が無いなら後で追加する */}
              </section>

              {/* ここから下は既存のDashboard内容 */}
              <section className="text-sm text-gray-600">
                ようこそ、{auth.user.name} さん
              </section>

            </div>
            <AttendanceCorrectionForm attendance={attendance} today={today} />
          </div>
          
        </div>
        
      </div>
    </AuthenticatedLayout>
  )
}
