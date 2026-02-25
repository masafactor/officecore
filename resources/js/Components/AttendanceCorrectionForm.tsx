import { router } from '@inertiajs/react'
import { useMemo, useState } from 'react'

type AttendanceMini = {
  id: number
  work_date: string // "YYYY-MM-DD"
  clock_in: string | null // "HH:MM"
  clock_out: string | null // "HH:MM"
}

type Props = {
  attendance: AttendanceMini | null
  today: string
}

const fmtTime = (t: string | null) => t ?? '—'
const pad2 = (n: number) => String(n).padStart(2, '0')

const addDaysYMD = (ymd: string, days: number) => {
  const [y, m, d] = ymd.split('-').map(Number)
  const utc = new Date(Date.UTC(y, m - 1, d))
  utc.setUTCDate(utc.getUTCDate() + days)
  return `${utc.getUTCFullYear()}-${pad2(utc.getUTCMonth() + 1)}-${pad2(utc.getUTCDate())}`
}

export default function AttendanceCorrectionForm({ attendance, today }: Props) {
  const [open, setOpen] = useState(false)

  const baseDate = attendance?.work_date ?? today

  const [clockInDate, setClockInDate] = useState(baseDate)
  const [clockInTime, setClockInTime] = useState(attendance?.clock_in ?? '')
  const [clockOutDate, setClockOutDate] = useState(baseDate)
  const [clockOutTime, setClockOutTime] = useState(attendance?.clock_out ?? '')
  const [reason, setReason] = useState('')
  const [note, setNote] = useState('')

  const reset = () => {
    setClockInDate(baseDate)
    setClockInTime(attendance?.clock_in ?? '')
    setClockOutDate(baseDate)
    setClockOutTime(attendance?.clock_out ?? '')
    setReason('')
    
  }

  const canSubmit = useMemo(() => {
    if (!attendance) return false
    if (!reason.trim()) return false
    if (!clockInTime && !clockOutTime && !note.trim()) return false
    return true
  }, [attendance, reason, clockInTime, clockOutTime, note])

  const submit = (e: React.FormEvent) => {
  e.preventDefault()
  

  const clockInAt =
  clockInTime ? `${clockInDate} ${clockInTime}` : null

  const clockOutAt =
    clockOutTime ? `${clockOutDate} ${clockOutTime}` : null

  router.post(`/attendances/${attendance?.id}/corrections`, {
    clock_in_at: clockInAt,
    clock_out_at: clockOutAt,
    reason: reason.trim() || null,
    note: note.trim() || null,
  })
  }

  return (
    <div className="mt-6">
      <div className="flex items-center justify-between">
        <div className="text-sm font-semibold text-gray-800">勤怠修正申請</div>

        <button
          type="button"
          disabled={!attendance}
          onClick={() => {
            if (!open) reset()
            setOpen((v) => !v)
          }}
          className="rounded-md border px-3 py-1.5 text-sm disabled:opacity-40"
        >
          {open ? '閉じる' : '修正申請する'}
        </button>
      </div>

      {!attendance && (
        <div className="mt-2 text-sm text-gray-500">
          本日の勤怠がないため申請できません。（先に出勤/退勤を登録してください）
        </div>
      )}

      {open && attendance && (
        <form onSubmit={submit} className="mt-3 rounded-lg border bg-white p-4 space-y-4">
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {/* 出勤 */}
            <div className="rounded-md border p-3">
              <div className="text-xs text-gray-500">出勤（元→申請）</div>
              <div className="mt-1 text-sm text-gray-700">{fmtTime(attendance.clock_in)} →</div>
              <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <input
                  type="date"
                  value={clockInDate}
                  onChange={(e) => setClockInDate(e.target.value)}
                  className="rounded-md border-gray-300 text-sm"
                />
                <input
                  type="time"
                  value={clockInTime}
                  onChange={(e) => setClockInTime(e.target.value)}
                  className="rounded-md border-gray-300 text-sm"
                />
              </div>
            </div>

            {/* 退勤 */}
            <div className="rounded-md border p-3">
              <div className="text-xs text-gray-500">退勤（元→申請）</div>
              <div className="mt-1 text-sm text-gray-700">{fmtTime(attendance.clock_out)} →</div>
              <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <input
                  type="date"
                  value={clockOutDate}
                  onChange={(e) => setClockOutDate(e.target.value)}
                  className="rounded-md border-gray-300 text-sm"
                />
                <input
                  type="time"
                  value={clockOutTime}
                  onChange={(e) => setClockOutTime(e.target.value)}
                  className="rounded-md border-gray-300 text-sm"
                />
              </div>

              <div className="mt-2 flex gap-2">
                <button
                  type="button"
                  onClick={() => setClockOutDate(addDaysYMD(clockOutDate || baseDate, 1))}
                  className="rounded-md border px-2 py-1 text-xs"
                >
                  退勤を翌日に
                </button>
                <button
                  type="button"
                  onClick={() => setClockOutDate(baseDate)}
                  className="rounded-md border px-2 py-1 text-xs"
                >
                  当日に戻す
                </button>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-xs text-gray-600">理由（必須）</label>
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              className="mt-1 w-full rounded-md border-gray-300 text-sm"
              rows={3}
              placeholder="例）打刻忘れ、電車遅延など"
            />
          </div>

          <div>
            <label className="block text-xs text-gray-600">メモ（任意）</label>
            <textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
              className="mt-1 w-full rounded-md border-gray-300 text-sm"
              rows={2}
            />
          </div>


          <div className="flex gap-2">
            <button
              type="submit"
              disabled={!canSubmit}
              className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
            >
              申請する
            </button>
            <button
              type="button"
              onClick={reset}
              className="rounded-md border px-4 py-2"
            >
              リセット
            </button>
          </div>

          <div className="text-xs text-gray-500">※申請後は管理者が確認して反映します。</div>
        </form>
      )}
    </div>
  )
}
