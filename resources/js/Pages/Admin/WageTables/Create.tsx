import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, useForm } from '@inertiajs/react'

type EmploymentType = {
  id: number
  code: string
  name: string
}

type Props = {
  employmentTypes: EmploymentType[]
}

export default function Create({ employmentTypes }: Props) {
  const form = useForm({
    employment_type_id: '',
    code: '',
    name: '',
    hourly_wage: '',
    start_date: '',
    end_date: '',
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()

    form.post(route('admin.wage-tables.store'), {
      preserveScroll: true,
    })
  }

  return (
    <AuthenticatedLayout
      header={<h2 className="text-xl font-semibold leading-tight text-gray-800">賃金テーブル作成</h2>}
    >
      <Head title="賃金テーブル作成" />

      <div className="py-10">
        <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="p-6 md:p-8">
              <div className="mb-6 flex items-center justify-between">
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">賃金テーブル新規作成</h1>
                  <p className="mt-1 text-sm text-gray-500">
                    アルバイト等の賃金テーブルを登録します。
                  </p>
                </div>

                <Link
                  href={route('admin.wage-tables.index')}
                  className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                >
                  一覧へ戻る
                </Link>
              </div>

              <form onSubmit={submit} className="space-y-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">雇用形態</label>
                    <select
                      value={form.data.employment_type_id}
                      onChange={(e) => form.setData('employment_type_id', e.target.value)}
                      className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    >
                      <option value="">選択してください</option>
                      {employmentTypes.map((type) => (
                        <option key={type.id} value={String(type.id)}>
                          {type.name}
                        </option>
                      ))}
                    </select>
                    {form.errors.employment_type_id && (
                      <div className="mt-1 text-xs text-red-600">{form.errors.employment_type_id}</div>
                    )}
                  </div>

                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">コード</label>
                    <input
                      type="text"
                      value={form.data.code}
                      onChange={(e) => form.setData('code', e.target.value)}
                      placeholder="例: pt_1_2026"
                      className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                    {form.errors.code && (
                      <div className="mt-1 text-xs text-red-600">{form.errors.code}</div>
                    )}
                  </div>

                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">名称</label>
                    <input
                      type="text"
                      value={form.data.name}
                      onChange={(e) => form.setData('name', e.target.value)}
                      placeholder="例: アルバイト1等級"
                      className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                    {form.errors.name && (
                      <div className="mt-1 text-xs text-red-600">{form.errors.name}</div>
                    )}
                  </div>

                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">時給</label>
                    <input
                      type="number"
                      min="0"
                      value={form.data.hourly_wage}
                      onChange={(e) => form.setData('hourly_wage', e.target.value)}
                      placeholder="例: 1200"
                      className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                    {form.errors.hourly_wage && (
                      <div className="mt-1 text-xs text-red-600">{form.errors.hourly_wage}</div>
                    )}
                  </div>

                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">開始日</label>
                    <input
                      type="date"
                      value={form.data.start_date}
                      onChange={(e) => form.setData('start_date', e.target.value)}
                      className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                    {form.errors.start_date && (
                      <div className="mt-1 text-xs text-red-600">{form.errors.start_date}</div>
                    )}
                  </div>

                  <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700">終了日</label>
                    <input
                      type="date"
                      value={form.data.end_date}
                      onChange={(e) => form.setData('end_date', e.target.value)}
                      className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                    {form.errors.end_date && (
                      <div className="mt-1 text-xs text-red-600">{form.errors.end_date}</div>
                    )}
                  </div>
                </div>

                <div className="flex gap-3">
                  <button
                    type="submit"
                    disabled={form.processing}
                    className="inline-flex h-11 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                  >
                    登録する
                  </button>

                  <Link
                    href={route('admin.wage-tables.index')}
                    className="inline-flex h-11 items-center justify-center rounded-lg bg-gray-200 px-5 text-sm font-semibold text-gray-800 transition hover:bg-gray-300"
                  >
                    キャンセル
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}