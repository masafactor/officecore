import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, usePage } from '@inertiajs/react'

type Props = {
  rule: {
    id: number
    name: string
    work_start: string | null
    work_end: string | null
    break_start: string | null
    break_end: string | null
  }
}

export default function Edit({ rule }: Props) {
  const flash = usePage<any>().props.flash

 const hhmm = (v: string | null) => (v ? v.slice(0, 5) : '')

  const { data, setData, patch, processing, errors } = useForm({
    work_start: hhmm(rule.work_start),
    work_end: hhmm(rule.work_end),
    break_start: hhmm(rule.break_start),
    break_end: hhmm(rule.break_end),
  })


  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    patch(route('admin.work-rules.update'))
  }

  return (
    <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">勤務ルール（管理者）</h2>}>
      <Head title="勤務ルール" />

      <div className="py-12">
        <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 space-y-6">

              {flash?.success && (
                <div className="rounded-md bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>
              )}

              <div className="text-sm text-gray-600">編集対象：{rule.name}</div>

              <form onSubmit={submit} className="space-y-5">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <Field
                    label="勤務開始"
                    value={data.work_start}
                    onChange={(v) => setData('work_start', v)}
                    error={errors.work_start}
                  />
                  <Field
                    label="勤務終了"
                    value={data.work_end}
                    onChange={(v) => setData('work_end', v)}
                    error={errors.work_end}
                  />
                  <Field
                    label="休憩開始"
                    value={data.break_start}
                    onChange={(v) => setData('break_start', v)}
                    error={errors.break_start}
                  />
                  <Field
                    label="休憩終了"
                    value={data.break_end}
                    onChange={(v) => setData('break_end', v)}
                    error={errors.break_end}
                  />
                </div>

                <button
                  type="submit"
                  disabled={processing}
                  className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-40"
                >
                  保存
                </button>
              </form>

              <div className="text-xs text-gray-500">
                ※ Step1：固定ルール「通常勤務」だけ編集できるようにしています
              </div>

            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}

function Field({
  label,
  value,
  onChange,
  error,
}: {
  label: string
  value: string
  onChange: (v: string) => void
  error?: string
}) {
  return (
    <div>
      <label className="block text-xs text-gray-600">{label}</label>
      <input
        type="time"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="mt-1 w-full rounded-md border-gray-300 text-sm"
      />
      {error && <div className="mt-1 text-xs text-red-600">{error}</div>}
    </div>
  )
}
