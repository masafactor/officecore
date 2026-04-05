import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Allowance = {
  id: number;
  start_date: string | null;
  end_date: string | null;
  from_place: string;
  to_place: string;
  amount: number;
  pass_type: string;
  status: string;
  note: string | null;
};

type Props = {
  allowances: Allowance[];
};

const passTypeLabel = (value: string) => {
  switch (value) {
    case 'monthly':
      return '1か月';
    case 'three_month':
      return '3か月';
    case 'six_month':
      return '6か月';
    default:
      return value;
  }
};

export default function Index({ allowances }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title="通勤定期一覧" />

      <div className="mx-auto max-w-6xl p-6">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold">通勤定期一覧</h1>
          <Link
            href={route('commuter-allowances.create')}
            className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
          >
            新規申請
          </Link>
        </div>

        <div className="overflow-x-auto rounded border bg-white">
          <table className="min-w-full border-collapse">
            <thead className="bg-gray-100">
              <tr>
                <th className="border px-4 py-2 text-left">適用開始日</th>
                <th className="border px-4 py-2 text-left">適用終了日</th>
                <th className="border px-4 py-2 text-left">区間</th>
                <th className="border px-4 py-2 text-right">金額</th>
                <th className="border px-4 py-2 text-left">定期種別</th>
                <th className="border px-4 py-2 text-left">状態</th>
                <th className="border px-4 py-2 text-left">備考</th>
              </tr>
            </thead>
            <tbody>
              {allowances.length === 0 ? (
                <tr>
                  <td colSpan={7} className="border px-4 py-6 text-center text-gray-500">
                    通勤定期データはありません。
                  </td>
                </tr>
              ) : (
                allowances.map((allowance) => (
                  <tr key={allowance.id}>
                    <td className="border px-4 py-2">{allowance.start_date ?? '-'}</td>
                    <td className="border px-4 py-2">{allowance.end_date ?? '-'}</td>
                    <td className="border px-4 py-2">
                      {allowance.from_place} ～ {allowance.to_place}
                    </td>
                    <td className="border px-4 py-2 text-right">
                      {allowance.amount.toLocaleString()}円
                    </td>
                    <td className="border px-4 py-2">{passTypeLabel(allowance.pass_type)}</td>
                    <td className="border px-4 py-2">{allowance.status}</td>
                    <td className="border px-4 py-2">{allowance.note ?? '-'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}