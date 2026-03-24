import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head,Link ,router, usePage } from '@inertiajs/react'

type WageTableRow = {
  id: number
  code: string
  name: string
  hourly_wage: number
  start_date: string | null
  end_date: string | null
  employment_type: {
    id: number
    code: string
    name: string
  } | null
}

type Props = {
  wageTables: WageTableRow[]
}



const handleDelete = (id: number, name: string) => {
  if (!window.confirm(`「${name}」を削除しますか？`)) {
    return
  }

  router.delete(route('admin.wage-tables.destroy', id), {
    preserveScroll: true,
  })
}


export default function Index({ wageTables }: Props) {

    const flash = usePage<any>().props.flash
                    
  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">賃金テーブル管理</h2>}
    >
      <Head title="賃金テーブル管理" />

      <div className="py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="p-6 md:p-8">
              <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">賃金テーブル一覧</h1>
                <p className="mt-1 text-sm text-gray-500">
                  雇用形態ごとの賃金テーブルを確認できます。
                </p>

                 <Link
                    href={route('admin.wage-tables.create')}
                    className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                    新規作成
                </Link>
              </div>

              <section className="overflow-x-auto">
                {(flash?.success || flash?.error) && (
                <div className="mb-4 space-y-2">
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
                <table className="min-w-full border-collapse">
                  <thead>
                    <tr className="bg-gray-100 text-left">
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">コード</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">名称</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">雇用形態</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">時給</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">開始日</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">終了日</th>
                      <th className="border px-4 py-3 text-sm font-semibold text-gray-700">操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    {wageTables.length === 0 ? (
                      <tr>
                        <td colSpan={7} className="border px-4 py-8 text-center text-sm text-gray-500">
                          賃金テーブルがありません
                        </td>
                      </tr>
                    ) : (
                      wageTables.map((row) => (
                        <tr key={row.id} className="hover:bg-gray-50">
                          <td className="border px-4 py-3 text-sm">{row.code}</td>
                          <td className="border px-4 py-3 text-sm">{row.name}</td>
                          <td className="border px-4 py-3 text-sm">
                            {row.employment_type?.name ?? '—'}
                          </td>
                          <td className="border px-4 py-3 text-sm">
                            {row.hourly_wage.toLocaleString()}円
                          </td>
                          <td className="border px-4 py-3 text-sm">{row.start_date ?? '—'}</td>
                          <td className="border px-4 py-3 text-sm">{row.end_date ?? '—'}</td>
                          <td className="border px-4 py-3 text-sm">
                            <Link
                                href={route('admin.wage-tables.edit', row.id)}
                                className="inline-flex rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                            >
                                編集
                            </Link>

                            <button
                            type="button"
                            onClick={() => handleDelete(row.id, row.name)}
                            className="inline-flex rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                            >
                            削除
                            </button>
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