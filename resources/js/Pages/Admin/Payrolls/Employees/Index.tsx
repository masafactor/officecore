import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'

type EmployeePayrollRow = {
  user_id: number
  user_name: string
  employment_type_name: string | null
  base_salary: number
  scheduled_hours: number
  overtime_hours: number
  hourly_rate: number
  overtime_amount: number
  salary_start_date: string | null
  salary_end_date: string | null
  salary_reason: string | null
  estimated_amount: number
}

type Summary = {
  user_count: number
  base_salary_total: number
  overtime_amount_total: number
  estimated_amount_total: number
}
type Props = {
  month: string
  rows: EmployeePayrollRow[]
  summary: Summary
}

export default function Index({ month, rows, summary }: Props) {
  const changeMonth = (value: string) => {
    router.get(
      route('admin.payrolls.employees.index'),
      { month: value },
      { preserveState: true, replace: true }
    )
  }

  const buildCsvUrl = () => {
    const params = new URLSearchParams()

    if (month) params.append('month', month)

    const query = params.toString()

    return query
      ? `${route('admin.payrolls.employees.csv')}?${query}`
      : route('admin.payrolls.employees.csv')
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">正社員給与確認</h2>}
    >
      <Head title="正社員給与確認" />

      <div className="py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="p-6 md:p-8">
              <div className="mb-6 flex items-end justify-between gap-4">
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">正社員給与確認</h1>
                  <p className="mt-1 text-sm text-gray-500">
                    固定給の正社員給与を月次で確認します。
                  </p>
                </div>

                <div className="flex items-end gap-3">
                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">対象月</label>
                    <input
                      type="month"
                      value={month}
                      onChange={(e) => changeMonth(e.target.value)}
                      className="h-11 rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                  </div>

                  <a
                    href={buildCsvUrl()}
                    className="inline-flex h-11 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700"
                  >
                    CSV出力
                  </a>
                </div>
              </div>

              <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">対象人数</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">{summary.user_count}名</div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">固定給合計</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">
                    {summary.base_salary_total.toLocaleString()}
                    <span className="ml-1 text-sm font-medium text-gray-500">円</span>
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">残業代合計</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">
                    {summary.overtime_amount_total.toLocaleString()}
                    <span className="ml-1 text-sm font-medium text-gray-500">円</span>
                  </div>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                  <div className="text-xs font-medium text-blue-600">支給見込額合計</div>
                  <div className="mt-2 text-2xl font-bold text-blue-700">
                    {summary.estimated_amount_total.toLocaleString()}
                    <span className="ml-1 text-sm font-medium text-blue-500">円</span>
                  </div>
                </div>
              </div>

              <section className="overflow-x-auto">
                <table className="min-w-full border-collapse">
                  <thead>
                    <tr className="bg-gray-100 text-left">
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">社員名</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">雇用形態</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">固定給</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">所定労働時間</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">残業時間</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">時間単価</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">残業代</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">適用開始日</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">理由</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">支給見込額</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={10} className="border px-4 py-8 text-center text-sm text-gray-500">
                          対象データがありません
                        </td>
                      </tr>
                    ) : (
                      rows.map((row) => (
                        <tr key={row.user_id} className="hover:bg-gray-50">
                          <td className="border px-4 py-3 text-sm">{row.user_name}</td>
                          <td className="border px-4 py-3 text-sm">{row.employment_type_name ?? '—'}</td>
                          <td className="border px-4 py-3 text-sm">{row.base_salary.toLocaleString()}円</td>
                          <td className="border px-4 py-3 text-sm">{row.scheduled_hours}</td>
                          <td className="border px-4 py-3 text-sm">{row.overtime_hours}</td>
                          <td className="border px-4 py-3 text-sm">{row.hourly_rate.toLocaleString()}円</td>
                          <td className="border px-4 py-3 text-sm">{row.overtime_amount.toLocaleString()}円</td>
                          <td className="border px-4 py-3 text-sm">{row.salary_start_date ?? '—'}</td>
                          <td className="border px-4 py-3 text-sm">{row.salary_reason ?? '—'}</td>
                          <td className="border px-4 py-3 text-sm font-semibold">
                            {row.estimated_amount.toLocaleString()}円
                          </td>
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