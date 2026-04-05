import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';


type Option = {
  value: string;
  label: string;
};

type Props = {
  passTypeOptions: Option[];
};

export default function Create({ passTypeOptions }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    start_date: '',
    end_date: '',
    from_place: '',
    to_place: '',
    amount: '',
    pass_type: 'monthly',
    note: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    post(route('commuter-allowances.store'));
  };

  return (
    <AuthenticatedLayout>
      <Head title="通勤定期申請" />

      <div className="mx-auto max-w-3xl p-6">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold">通勤定期申請</h1>
          <Link
            href={route('commuter-allowances.index')}
            className="rounded border px-4 py-2 hover:bg-gray-50"
          >
            一覧へ戻る
          </Link>
        </div>

        <form onSubmit={submit} className="space-y-6 rounded border bg-white p-6">
          <div>
            <label className="mb-1 block text-sm font-medium">適用開始日</label>
            <input
              type="date"
              value={data.start_date}
              onChange={(e) => setData('start_date', e.target.value)}
              className="w-full rounded border px-3 py-2"
            />
            {errors.start_date && <div className="mt-1 text-sm text-red-600">{errors.start_date}</div>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">適用終了日</label>
            <input
              type="date"
              value={data.end_date}
              onChange={(e) => setData('end_date', e.target.value)}
              className="w-full rounded border px-3 py-2"
            />
            {errors.end_date && <div className="mt-1 text-sm text-red-600">{errors.end_date}</div>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">出発地</label>
            <input
              type="text"
              value={data.from_place}
              onChange={(e) => setData('from_place', e.target.value)}
              className="w-full rounded border px-3 py-2"
            />
            {errors.from_place && <div className="mt-1 text-sm text-red-600">{errors.from_place}</div>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">到着地</label>
            <input
              type="text"
              value={data.to_place}
              onChange={(e) => setData('to_place', e.target.value)}
              className="w-full rounded border px-3 py-2"
            />
            {errors.to_place && <div className="mt-1 text-sm text-red-600">{errors.to_place}</div>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">金額</label>
            <input
              type="number"
              min="0"
              value={data.amount}
              onChange={(e) => setData('amount', e.target.value)}
              className="w-full rounded border px-3 py-2"
            />
            {errors.amount && <div className="mt-1 text-sm text-red-600">{errors.amount}</div>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">定期種別</label>
            <select
              value={data.pass_type}
              onChange={(e) => setData('pass_type', e.target.value)}
              className="w-full rounded border px-3 py-2"
            >
              {passTypeOptions.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
            {errors.pass_type && <div className="mt-1 text-sm text-red-600">{errors.pass_type}</div>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">備考</label>
            <textarea
              value={data.note}
              onChange={(e) => setData('note', e.target.value)}
              className="w-full rounded border px-3 py-2"
              rows={4}
            />
            {errors.note && <div className="mt-1 text-sm text-red-600">{errors.note}</div>}
          </div>

          <div className="flex justify-end gap-2">
            <Link
              href={route('commuter-allowances.index')}
              className="rounded border px-4 py-2 hover:bg-gray-50"
            >
              キャンセル
            </Link>
            <button
              type="submit"
              disabled={processing}
              className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
            >
              登録する
            </button>
          </div>
        </form>
      </div>
    </AuthenticatedLayout>
  );
}