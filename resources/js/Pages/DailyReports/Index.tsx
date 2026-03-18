import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router, useForm, usePage } from '@inertiajs/react'
import { useEffect, useState } from 'react'

type HistoryRow = {
  id: number
  report_date: string
  status: string
  content: string | null
  updated_at: string | null
}

type Props = {
  date: string // "YYYY-MM-DD"
  current: {
    id: number
    report_date: string
    status: string
    content: string | null
    updated_at: string | null
  } | null
  history: HistoryRow[]
}

export default function Index({ date, current, history }: Props) {
  const flash = usePage<any>().props.flash

  const form = useForm({
    report_date: date,
    content: current?.content ?? '',
    status: current?.status ?? 'draft',
  })

  // 日付が変わったらフォームを同期（Inertia遷移時のズレ防止）
  useEffect(() => {
    form.setData('report_date', date)
    form.setData('content', current?.content ?? '')
    form.setData('status', current?.status ?? 'draft')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [date, current?.id])

  const changeDate = (v: string) => {
    router.get(route('daily-reports.index'), { date: v }, { preserveState: true })
  }

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    form.post(route('daily-reports.store'), { preserveScroll: true })
  }

  
  const getThisWeekMonday = () => {
  const today = new Date();
  const day = today.getDay(); // 0: 日, 1: 月, ..., 6: 土
  const diff = day === 0 ? -6 : 1 - day; // 月曜始まりに合わせる

  const monday = new Date(today);
  monday.setDate(today.getDate() + diff);

  const yyyy = monday.getFullYear();
  const mm = String(monday.getMonth() + 1).padStart(2, '0');
  const dd = String(monday.getDate()).padStart(2, '0');

  return `${yyyy}-${mm}-${dd}`;
};


  const [weekStart, setWeekStart] = useState(getThisWeekMonday());

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">勤務日報</h2>}>
      <Head title="勤務日報" />


      <div className="py-12">
        
        <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            
            <div className="p-6 space-y-6">
                <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="mb-3 text-sm font-semibold text-gray-800">週間日報PDF</div>

                  <form
                    method="GET"
                    action={route('daily-reports.pdf.weekly')}
                    target="_blank"
                    className="flex flex-col gap-3 sm:flex-row sm:items-end"
                  >
                    <div className="flex flex-col">
                      <label className="mb-1 text-sm font-medium text-gray-700">週の開始日</label>
                      <input
                        type="date"
                        name="week_start"
                        value={weekStart}
                        onChange={(e) => setWeekStart(e.target.value)}
                        className="h-11 rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                      />
                    </div>

                    <button
                      type="submit"
                      className="inline-flex h-11 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 active:translate-y-px"
                    >
                      1週間分PDF出力
                    </button>
                  </form>
                </section>

              {(flash?.success || flash?.error) && (
                <div className="space-y-2">
                  {flash?.success && <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                  {flash?.error && <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}
                </div>
              )}

              <h1 className="text-2xl font-bold text-gray-900">本日の日報</h1>

              <form onSubmit={submit} className="space-y-4">
                <div className="flex flex-wrap items-end gap-3">
                  
                  <div>
                    <label className="block text-xs text-gray-600">対象日</label>
                    <input
                      type="date"
                      value={date}
                      onChange={(e) => changeDate(e.target.value)}
                      className="mt-1 rounded-md border-gray-300 text-sm"
                    />
                  </div>

                  <div className="ml-auto text-xs text-gray-500">
                    {current?.updated_at ? `最終更新：${current.updated_at}` : '未保存'}
                  </div>
                </div>

                <div>
                  <label className="block text-xs text-gray-600">内容</label>
                  <textarea
                    value={form.data.content}
                    onChange={(e) => form.setData('content', e.target.value)}
                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                    rows={10}
                    placeholder="今日やったこと、詰まったこと、明日やること…など"
                  />
                  {form.errors.content && <div className="mt-1 text-xs text-red-600">{form.errors.content}</div>}
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium text-gray-700">状態</label>
                  <select
                    value={form.data.status}
                    onChange={(e) => form.setData('status', e.target.value)}
                    className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  >
                    <option value="draft">下書き</option>
                    <option value="submitted">提出</option>
                  </select>
                </div>

                <button
                  type="submit"
                  disabled={form.processing}
                  className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
                >
                  保存
                </button>
              </form>

              <hr />

              <section className="space-y-3">
                <div className="flex items-center">
                  <h3 className="text-sm font-semibold text-gray-800">履歴（直近30件）</h3>
                </div>

                {history.length === 0 ? (
                  <div className="text-sm text-gray-600">日報はまだありません。</div>
                ) : (
                  <div className="space-y-2">
                    {history.map((r) => (
                      <button
                        key={r.id}
                        type="button"
                        onClick={() => changeDate(r.report_date)}
                        className="w-full rounded-md border p-3 text-left hover:bg-gray-50"
                      >
                        <div className="flex items-center gap-3">
                          <div className="text-sm font-semibold">{r.report_date.replaceAll('-', '/')}</div>
                          <div className="text-xs text-gray-500">{r.status}</div>
                          <div className="ml-auto text-xs text-gray-500">{r.updated_at ?? '—'}</div>
                        </div>
                        <div className="mt-2 line-clamp-2 text-sm text-gray-700">
                          {r.content?.trim() ? r.content : '（内容なし）'}
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </section>

            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
