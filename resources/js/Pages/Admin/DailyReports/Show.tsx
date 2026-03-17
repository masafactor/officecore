import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'

type Props = {
  dailyReport: {
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
}

export default function Show({ dailyReport }: Props) {
  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">日報詳細</h2>}
    >
      <Head title="日報詳細" />
      

      <div className="py-10">
        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">


                    <div className="flex items-center gap-2">
                        <a
                            href={route('admin.daily-reports.pdf.show', dailyReport.id)}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        >
                            PDF表示
                        </a>
                    </div>
            <div className="p-6 md:p-8">
              <div className="mb-6">
                <Link
                  href={route('admin.daily-reports.index')}
                  className="inline-flex rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300"
                >
                  一覧へ戻る
                </Link>
              </div>

              <div className="space-y-6">
                <div className="rounded-xl border border-gray-200 p-4">
                  <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                      <div className="mb-1 text-sm font-medium text-gray-500">ユーザー</div>
                      <div className="text-base text-gray-900">{dailyReport.user.name ?? '—'}</div>
                    </div>
                    <div>
                      <div className="mb-1 text-sm font-medium text-gray-500">対象日</div>
                      <div className="text-base text-gray-900">{dailyReport.report_date}</div>
                    </div>
                    <div>
                      <div className="mb-1 text-sm font-medium text-gray-500">状態</div>
                      <div className="text-base text-gray-900">{dailyReport.status}</div>
                    </div>
                    <div>
                      <div className="mb-1 text-sm font-medium text-gray-500">更新日時</div>
                      <div className="text-base text-gray-900">{dailyReport.updated_at ?? '—'}</div>
                    </div>
                  </div>
                </div>

                <div>
                  <div className="mb-2 text-sm font-medium text-gray-500">内容</div>
                  <div className="min-h-64 whitespace-pre-wrap rounded-xl border border-gray-200 p-4 text-sm text-gray-800">
                    {dailyReport.content?.trim() ? dailyReport.content : '（内容なし）'}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}