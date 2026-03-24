import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'

type PayrollRow = {
  user_id: number
  user_name: string
  wage_table_name: string | null
  hourly_wage: number
  worked_hours: number
  overtime_hours: number
  late_night_hours: number
  base_amount: number
  overtime_premium: number
  late_night_premium: number
  estimated_amount: number
}

type Props = {
  month: string
  rows: PayrollRow[]
}

export default function Index({ month, rows }: Props) {
  const changeMonth = (value: string) => {
    router.get(
      route('admin.payrolls.part-time.index'),
      { month: value },
      { preserveState: true, replace: true }
    )
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">アルバイト給与確認</h2>}
    >
      <Head title="アルバイト給与確認" />

      <div className="py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="p-6 md:p-8">
              <div className="mb-6 flex items-end justify-between gap-4">
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">アルバイト給与確認</h1>
                  <p className="mt-1 text-sm text-gray-500">
                    賃金テーブルと勤怠から支給見込額を確認します。
                  </p>
                </div>

                <div>
                  <label className="mb-1 block text-sm font-medium text-gray-700">対象月</label>
                  <input
                    type="month"
                    value={month}
                    onChange={(e) => changeMonth(e.target.value)}
                    className="h-11 rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  />
                </div>
              </div>

              <section className="overflow-x-auto">
                <table className="min-w-full border-collapse">
                  <thead>
                    <tr className="bg-gray-100 text-left">
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">社員名</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">賃金テーブル</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">時給</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">総労働時間</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">残業時間</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">深夜時間</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">基本給</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">残業割増</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">深夜割増</th>
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
                          <td className="border px-4 py-3 text-sm">{row.wage_table_name ?? '—'}</td>
                          <td className="border px-4 py-3 text-sm">{row.hourly_wage.toLocaleString()}円</td>
                          <td className="border px-4 py-3 text-sm">{row.worked_hours}</td>
                          <td className="border px-4 py-3 text-sm">{row.overtime_hours}</td>
                          <td className="border px-4 py-3 text-sm">{row.late_night_hours}</td>
                          <td className="border px-4 py-3 text-sm">{row.base_amount.toLocaleString()}円</td>
                          <td className="border px-4 py-3 text-sm">{row.overtime_premium.toLocaleString()}円</td>
                          <td className="border px-4 py-3 text-sm">{row.late_night_premium.toLocaleString()}円</td>
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