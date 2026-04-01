import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, usePage, router } from '@inertiajs/react'

type CalendarDay = {
  id: number
  calendar_date: string
  day_type: 'workday' | 'holiday' | 'shortday'
  scheduled_minutes: number
  note: string | null
}

type Props = {
  year: number
  days: CalendarDay[]
}

export default function Index({ year, days }: Props) {
  const flash = usePage<any>().props.flash

  const createForm = useForm({
    calendar_date: '',
    day_type: 'workday',
    scheduled_minutes: '480',
    note: '',
  })

  const generateForm = useForm({
    year: String(year),
  })

  const submitCreate = (e: React.FormEvent) => {
    e.preventDefault()
    createForm.post(route('admin.company-calendar.store'), {
      preserveScroll: true,
    })
  }

  const submitGenerate = (e: React.FormEvent) => {
    e.preventDefault()
    generateForm.post(route('admin.company-calendar.generate-year'), {
      preserveScroll: true,
    })
  }

  const changeYear = (value: string) => {
    router.get(
      route('admin.company-calendar.index'),
      { year: value },
      { preserveState: true, replace: true }
    )
  }

  const bulkForm = useForm({
  start_date: '',
  end_date: '',
  day_type: 'holiday',
  scheduled_minutes: '0',
  note: '',
})

const submitBulk = (e: React.FormEvent) => {
  e.preventDefault()
  bulkForm.post(route('admin.company-calendar.bulk-update'), {
    preserveScroll: true,
  })
}

const weekdayForm = useForm({
  start_date: '',
  end_date: '',
  weekdays: [] as string[],
  day_type: 'holiday',
  scheduled_minutes: '0',
  note: '',
})

const submitWeekdayUpdate = (e: React.FormEvent) => {
  e.preventDefault()

  weekdayForm.post(route('admin.company-calendar.update-weekdays'), {
    preserveScroll: true,
  })
}

const toggleWeekday = (value: string) => {
  if (weekdayForm.data.weekdays.includes(value)) {
    weekdayForm.setData(
      'weekdays',
      weekdayForm.data.weekdays.filter((v) => v !== value)
    )
  } else {
    weekdayForm.setData('weekdays', [...weekdayForm.data.weekdays, value])
  }
}



  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">会社カレンダー管理</h2>}
    >
      <Head title="会社カレンダー管理" />

      <div className="py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="p-6 md:p-8 space-y-6">
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

              <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">会社カレンダー</h1>
                  <p className="mt-1 text-sm text-gray-500">
                    休日、短縮勤務日、所定労働時間を管理します。
                  </p>
                </div>

                <div>
                  <label className="mb-1 block text-sm font-medium text-gray-700">対象年</label>
                  <input
                    type="number"
                    value={year}
                    onChange={(e) => changeYear(e.target.value)}
                    className="h-11 rounded-lg border border-gray-300 px-3 text-sm"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <h3 className="mb-4 text-base font-semibold text-gray-800">1日登録</h3>

                  <form onSubmit={submitCreate} className="space-y-4">
                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">日付</label>
                      <input
                        type="date"
                        value={createForm.data.calendar_date}
                        onChange={(e) => createForm.setData('calendar_date', e.target.value)}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      />
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">種別</label>
                      <select
                        value={createForm.data.day_type}
                        onChange={(e) => createForm.setData('day_type', e.target.value as 'workday' | 'holiday' | 'shortday')}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      >
                        <option value="workday">通常勤務日</option>
                        <option value="holiday">休日</option>
                        <option value="shortday">短縮勤務日</option>
                      </select>
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">所定労働分</label>
                      <input
                        type="number"
                        min="0"
                        value={createForm.data.scheduled_minutes}
                        onChange={(e) => createForm.setData('scheduled_minutes', e.target.value)}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      />
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">メモ</label>
                      <input
                        type="text"
                        value={createForm.data.note}
                        onChange={(e) => createForm.setData('note', e.target.value)}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      />
                    </div>

                    <button
                      type="submit"
                      disabled={createForm.processing}
                      className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      登録
                    </button>
                  </form>
                </section>

                <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <h3 className="mb-4 text-base font-semibold text-gray-800">範囲更新</h3>

                  <form onSubmit={submitBulk} className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">開始日</label>
                        <input
                          type="date"
                          value={bulkForm.data.start_date}
                          onChange={(e) => bulkForm.setData('start_date', e.target.value)}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        />
                      </div>

                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">終了日</label>
                        <input
                          type="date"
                          value={bulkForm.data.end_date}
                          onChange={(e) => bulkForm.setData('end_date', e.target.value)}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        />
                      </div>

                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">種別</label>
                        <select
                          value={bulkForm.data.day_type}
                          onChange={(e) => bulkForm.setData('day_type', e.target.value as 'workday' | 'holiday' | 'shortday')}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        >
                          <option value="workday">通常勤務日</option>
                          <option value="holiday">休日</option>
                          <option value="shortday">短縮勤務日</option>
                        </select>
                      </div>

                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">所定労働分</label>
                        <input
                          type="number"
                          min="0"
                          value={bulkForm.data.scheduled_minutes}
                          onChange={(e) => bulkForm.setData('scheduled_minutes', e.target.value)}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">メモ</label>
                      <input
                        type="text"
                        value={bulkForm.data.note}
                        onChange={(e) => bulkForm.setData('note', e.target.value)}
                        placeholder="例: 盆休み"
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      />
                    </div>

                    <button
                      type="submit"
                      disabled={bulkForm.processing}
                      className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      範囲更新
                    </button>
                  </form>
                </section>
                <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <h3 className="mb-4 text-base font-semibold text-gray-800">曜日指定更新</h3>

                  <form onSubmit={submitWeekdayUpdate} className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">開始日</label>
                        <input
                          type="date"
                          value={weekdayForm.data.start_date}
                          onChange={(e) => weekdayForm.setData('start_date', e.target.value)}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        />
                        {weekdayForm.errors.start_date && (
                          <div className="mt-1 text-xs text-red-600">{weekdayForm.errors.start_date}</div>
                        )}
                      </div>

                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">終了日</label>
                        <input
                          type="date"
                          value={weekdayForm.data.end_date}
                          onChange={(e) => weekdayForm.setData('end_date', e.target.value)}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        />
                        {weekdayForm.errors.end_date && (
                          <div className="mt-1 text-xs text-red-600">{weekdayForm.errors.end_date}</div>
                        )}
                      </div>
                    </div>

                    <div>
                      <label className="mb-2 block text-sm font-medium text-gray-700">対象曜日</label>
                      <div className="flex flex-wrap gap-2">
                        {[
                          ['0', '日'],
                          ['1', '月'],
                          ['2', '火'],
                          ['3', '水'],
                          ['4', '木'],
                          ['5', '金'],
                          ['6', '土'],
                        ].map(([value, label]) => {
                          const checked = weekdayForm.data.weekdays.includes(value)

                          return (
                            <button
                              key={value}
                              type="button"
                              onClick={() => toggleWeekday(value)}
                              className={`rounded-lg border px-4 py-2 text-sm font-medium ${
                                checked
                                  ? 'border-blue-600 bg-blue-50 text-blue-700'
                                  : 'border-gray-300 bg-white text-gray-700'
                              }`}
                            >
                              {label}
                            </button>
                          )
                        })}
                      </div>
                      {weekdayForm.errors.weekdays && (
                        <div className="mt-1 text-xs text-red-600">{weekdayForm.errors.weekdays}</div>
                      )}
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">種別</label>
                        <select
                          value={weekdayForm.data.day_type}
                          onChange={(e) =>
                            weekdayForm.setData('day_type', e.target.value as 'workday' | 'holiday' | 'shortday')
                          }
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        >
                          <option value="workday">通常勤務日</option>
                          <option value="holiday">休日</option>
                          <option value="shortday">短縮勤務日</option>
                        </select>
                        {weekdayForm.errors.day_type && (
                          <div className="mt-1 text-xs text-red-600">{weekdayForm.errors.day_type}</div>
                        )}
                      </div>

                      <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">所定労働分</label>
                        <input
                          type="number"
                          min="0"
                          value={weekdayForm.data.scheduled_minutes}
                          onChange={(e) => weekdayForm.setData('scheduled_minutes', e.target.value)}
                          className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                        />
                        {weekdayForm.errors.scheduled_minutes && (
                          <div className="mt-1 text-xs text-red-600">{weekdayForm.errors.scheduled_minutes}</div>
                        )}
                      </div>
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">メモ</label>
                      <input
                        type="text"
                        value={weekdayForm.data.note}
                        onChange={(e) => weekdayForm.setData('note', e.target.value)}
                        placeholder="例: 毎週月曜休み"
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      />
                      {weekdayForm.errors.note && (
                        <div className="mt-1 text-xs text-red-600">{weekdayForm.errors.note}</div>
                      )}
                    </div>

                    <button
                      type="submit"
                      disabled={weekdayForm.processing}
                      className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      曜日指定更新
                    </button>
                  </form>
                </section>

                <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <h3 className="mb-4 text-base font-semibold text-gray-800">年次生成</h3>

                  <form onSubmit={submitGenerate} className="space-y-4">
                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">対象年</label>
                      <input
                        type="number"
                        value={generateForm.data.year}
                        onChange={(e) => generateForm.setData('year', e.target.value)}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"
                      />
                    </div>

                    <div className="text-sm text-gray-500">
                      土日を休日、平日を通常勤務日として未登録日を一括生成します。
                    </div>

                    <button
                      type="submit"
                      disabled={generateForm.processing}
                      className="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black disabled:opacity-50"
                    >
                      年間生成
                    </button>
                  </form>
                </section>
              </div>

              <section className="overflow-x-auto">
                <table className="min-w-full border-collapse">
                  <thead>
                    <tr className="bg-gray-100 text-left">
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">日付</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">種別</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">所定労働分</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">メモ</th>
                    </tr>
                  </thead>
                  <tbody>
                    {days.length === 0 ? (
                      <tr>
                        <td colSpan={4} className="border px-4 py-8 text-center text-sm text-gray-500">
                          データがありません
                        </td>
                      </tr>
                    ) : (
                      days.map((day) => (
                        <tr key={day.id} className="hover:bg-gray-50">
                          <td className="border px-4 py-3 text-sm">{day.calendar_date}</td>
                          <td className="border px-4 py-3 text-sm">
                            {day.day_type === 'workday'
                              ? '通常勤務日'
                              : day.day_type === 'holiday'
                              ? '休日'
                              : '短縮勤務日'}
                          </td>
                          <td className="border px-4 py-3 text-sm">{day.scheduled_minutes}分</td>
                          <td className="border px-4 py-3 text-sm">{day.note ?? '—'}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </section>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}