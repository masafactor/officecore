import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { useState } from 'react'

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

type Summary = {
  user_count: number
  worked_hours_total: number
  overtime_hours_total: number
  late_night_hours_total: number
  base_amount_total: number
  estimated_amount_total: number
}

type WageTableOption = {
  id: number
  name: string
  hourly_wage: number
}


type Props = {
  month: string
  rows: PayrollRow[]
  summary: Summary
  wageTables: WageTableOption[]
  filters: {
    wage_table_id: string
  }
}


export default function Index({ month, rows,summary,wageTables, filters  }: Props) {


const [wageTableId, setWageTableId] = useState(filters.wage_table_id ?? '')

const changeMonth = (value: string) => {
  router.get(
    route('admin.payrolls.part-time.index'),
    {
      month: value,
      wage_table_id: wageTableId || undefined,
    },
    { preserveState: true, replace: true }
  )
}

const applyFilter = () => {
  router.get(
    route('admin.payrolls.part-time.index'),
    {
      month,
      wage_table_id: wageTableId || undefined,
    },
    { preserveState: true, replace: true }
  )
}

const resetFilter = () => {
  setWageTableId('')

  router.get(
    route('admin.payrolls.part-time.index'),
    { month },
    { preserveState: true, replace: true }
  )
}

const buildCsvUrl = () => {
  const params = new URLSearchParams()

  if (month) params.append('month', month)
  if (wageTableId) params.append('wage_table_id', wageTableId)

  const query = params.toString()

  return query
    ? `${route('admin.payrolls.part-time.csv')}?${query}`
    : route('admin.payrolls.part-time.csv')
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
               <div className="flex items-end gap-3">

                
               
                
                  <label className="mb-1 block text-sm font-medium text-gray-700">対象月</label>
                  <input
                    type="month"
                    value={month}
                    onChange={(e) => changeMonth(e.target.value)}
                    className="h-11 rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  />
                
                
  
                  <label className="mb-1 block text-sm font-medium text-gray-700">賃金テーブル</label>
                  <select
                    value={wageTableId}
                    onChange={(e) => setWageTableId(e.target.value)}
                    className="h-11 rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  >
                    <option value="">すべて</option>
                    {wageTables.map((table) => (
                      <option key={table.id} value={String(table.id)}>
                        {table.name}（{table.hourly_wage}円）
                      </option>
                    ))}
                  </select>


                  <button
                    type="button"
                    onClick={applyFilter}
                    className="inline-flex h-11 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700"
                  >
                    絞り込み
                  </button>

                  <button
                    type="button"
                    onClick={resetFilter}
                    className="inline-flex h-11 items-center justify-center rounded-lg bg-gray-200 px-4 text-sm font-semibold text-gray-800 hover:bg-gray-300"
                  >
                    リセット
                  </button>

                  <a
                    href={buildCsvUrl()}
                    className="inline-flex h-11 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700"
                  >
                    CSV出力
                  </a>
                </div>
                
              </div>
              <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">対象人数</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">{summary.user_count}名</div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">総労働時間合計</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">
                    {summary.worked_hours_total}
                    <span className="ml-1 text-sm font-medium text-gray-500">時間</span>
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">残業時間合計</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">
                    {summary.overtime_hours_total}
                    <span className="ml-1 text-sm font-medium text-gray-500">時間</span>
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">深夜時間合計</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">
                    {summary.late_night_hours_total}
                    <span className="ml-1 text-sm font-medium text-gray-500">時間</span>
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                  <div className="text-xs font-medium text-gray-500">基本給合計</div>
                  <div className="mt-2 text-2xl font-bold text-gray-900">
                    {summary.base_amount_total.toLocaleString()}
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