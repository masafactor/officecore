import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { FormEvent, useState } from 'react'

type UserOption = {
  id: number
  name: string
}

type DailyReportRow = {
  id: number
  report_date: string
  status: string
  content: string | null
  updated_at: string | null
  user: {
    id: number | null
    name: string | null
  }
}

type PaginatedDailyReports = {
  data: DailyReportRow[]
  links: {
    url: string | null
    label: string
    active: boolean
  }[]
}

type Props = {
  filters: {
    date: string
    user_id: string
    keyword: string
  }
  users: UserOption[]
  dailyReports: PaginatedDailyReports
}

export default function Index({ filters, users, dailyReports }: Props) {
  const [date, setDate] = useState(filters.date)
  const [userId, setUserId] = useState(filters.user_id)
  const [keyword, setKeyword] = useState(filters.keyword)

  const handleSearch = (e: FormEvent) => {
    e.preventDefault()

    router.get(
      route('admin.daily-reports.index'),
      {
        date,
        user_id: userId,
        keyword,
      },
      {
        preserveState: true,
        replace: true,
      }
    )
  }

  const handleReset = () => {
    setDate('')
    setUserId('')
    setKeyword('')

    router.get(
      route('admin.daily-reports.index'),
      {},
      {
        preserveState: true,
        replace: true,
      }
    )
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">日報管理</h2>}
    >
      <Head title="日報管理" />

      <div className="py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="p-6 md:p-8">
              <div className="space-y-6">
                <section className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <form onSubmit={handleSearch} className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">対象日</label>
                      <input
                        type="date"
                        value={date}
                        onChange={(e) => setDate(e.target.value)}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                      />
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">ユーザー</label>
                      <select
                        value={userId}
                        onChange={(e) => setUserId(e.target.value)}
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                      >
                        <option value="">すべて</option>
                        {users.map((user) => (
                          <option key={user.id} value={user.id}>
                            {user.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="mb-1 block text-sm font-medium text-gray-700">キーワード</label>
                      <input
                        type="text"
                        value={keyword}
                        onChange={(e) => setKeyword(e.target.value)}
                        placeholder="内容を検索"
                        className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                      />
                    </div>

                    <div className="flex items-end gap-2">
                      <button
                        type="submit"
                        className="inline-flex h-11 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700"
                      >
                        検索
                      </button>
                      <button
                        type="button"
                        onClick={handleReset}
                        className="inline-flex h-11 items-center justify-center rounded-lg bg-gray-200 px-4 text-sm font-semibold text-gray-800 transition hover:bg-gray-300"
                      >
                        リセット
                      </button>
                    </div>
                  </form>
                </section>

                <section className="overflow-x-auto">
                  <table className="min-w-full border-collapse">
                    <thead>
                      <tr className="bg-gray-100 text-left">
                        <th className="border px-4 py-3 text-sm font-semibold text-gray-700">日付</th>
                        <th className="border px-4 py-3 text-sm font-semibold text-gray-700">ユーザー</th>
                        <th className="border px-4 py-3 text-sm font-semibold text-gray-700">状態</th>
                        <th className="border px-4 py-3 text-sm font-semibold text-gray-700">内容</th>
                        <th className="border px-4 py-3 text-sm font-semibold text-gray-700">更新日時</th>
                        <th className="border px-4 py-3 text-sm font-semibold text-gray-700">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      {dailyReports.data.length === 0 ? (
                        <tr>
                          <td colSpan={6} className="border px-4 py-8 text-center text-sm text-gray-500">
                            日報がありません
                          </td>
                        </tr>
                      ) : (
                        dailyReports.data.map((row) => (
                          <tr key={row.id} className="hover:bg-gray-50">
                            <td className="border px-4 py-3 text-sm">{row.report_date}</td>
                            <td className="border px-4 py-3 text-sm">{row.user.name ?? '—'}</td>
                            <td className="border px-4 py-3 text-sm">{row.status}</td>
                            <td className="border px-4 py-3 text-sm text-gray-700">
                              <div className="line-clamp-2">
                                {row.content?.trim() ? row.content : '（内容なし）'}
                              </div>
                            </td>
                            <td className="border px-4 py-3 text-sm">{row.updated_at ?? '—'}</td>
                            <td className="border px-4 py-3 text-sm">
                              <Link
                                href={route('admin.daily-reports.show', row.id)}
                                className="inline-flex rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                              >
                                詳細
                              </Link>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </section>

                <section className="flex flex-wrap gap-2">
                  {dailyReports.links.map((link, index) => (
                    <button
                      key={`${link.label}-${index}`}
                      type="button"
                      disabled={!link.url}
                      onClick={() => link.url && router.visit(link.url)}
                      className={`rounded-lg px-3 py-2 text-sm ${
                        link.active
                          ? 'bg-blue-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      } disabled:cursor-not-allowed disabled:opacity-50`}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  ))}
                </section>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}