import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import AttendanceCorrectionForm from '@/Components/AttendanceCorrectionForm'

type Attendance = {
  id: number
  work_date: string
  clock_in: string | null
  clock_out: string | null
   overtime_now?: boolean
}

type Props = {
  auth: { user: { name: string } }
  today: string
  attendance: Attendance | null
  workedMinutes: number | null
  missingClockOutDates: string[] // ← 追加
}

export default function Dashboard({
  auth,
  today,
  attendance,
  workedMinutes,
  missingClockOutDates,
}: Props) {
  const hasClockIn = !!attendance?.clock_in
  const hasClockOut = !!attendance?.clock_out

  const clockIn = () => {
    router.post(route('attendance.clockIn'))
  }

  const clockOut = () => {
    router.post(route('attendance.clockOut'))
  }

 
  const fmtTime = (v: string | null) => {
  if (!v) return '—'

  // すでに "HH:MM" ならそのまま
  if (/^\d{2}:\d{2}$/.test(v)) return v

  // "YYYY-MM-DD HH:MM:SS" or ISO ("YYYY-MM-DDTHH:MM:SS") なら時刻だけ抜く
  const m = v.match(/(\d{2}:\d{2})/)
  return m ? m[1] : v
  }


  const fmtMinutes = (m: number | null) => {
    if (m == null) return '—'
    const h = Math.floor(m / 60)
    const mm = m % 60
    return `${h}h ${mm}m`
  }

  // YYYY-MM-DD -> YYYY/MM/DD
  const fmtDate = (d: string) => d.replaceAll('-', '/')

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>}
    >
      <Head title="Dashboard" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900 space-y-6">

              {/* 未退勤通知（Step1 必須） */}
              {missingClockOutDates?.length > 0 && (
                <section className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                  <div className="flex items-start justify-between gap-4">
                    <div className="space-y-2">
                      <div className="font-semibold text-amber-900">
                        未退勤があります（{missingClockOutDates.length}件）
                      </div>
                      <div className="text-sm text-amber-900/80">
                        退勤打刻が未入力の日付：
                      </div>
                    

                      <div className="flex flex-wrap gap-2">
                        {missingClockOutDates.slice(0, 10).map((d) => (
                          <span
                            key={d}
                            className="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm text-amber-900 ring-1 ring-amber-200"
                          >
                            {fmtDate(d)}
                          </span>
                        ))}
                        {missingClockOutDates.length > 10 && (
                          <span className="text-sm text-amber-900/70">
                            …ほか {missingClockOutDates.length - 10}件
                          </span>
                        )}
                      </div>

                      <div className="text-xs text-amber-900/70">
                        ※ 修正申請がまだの場合は、下のフォームか勤怠履歴から申請してください。
                      </div>
                    </div>
                  


                    {/* 一旦リンク先が無ければボタン無しでもOK。
                        後で「勤怠一覧」や「該当日へ移動」ができたら活かす */}
                    {/* <button className="rounded-md bg-amber-600 px-3 py-2 text-white">確認する</button> */}
                  </div>
                </section>
              )}
                {attendance?.overtime_now && attendance?.clock_in && !attendance?.clock_out && (
                      <span>🔥 勤務終了時刻を過ぎています</span>
                    )}

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
              </section>

              {/* 既存 */}
              <section className="text-sm text-gray-600">
                ようこそ、{auth.user.name} さん
              </section>

            </div>

            {/* 修正申請フォーム（既存） */}
            <AttendanceCorrectionForm attendance={attendance} today={today} />
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
