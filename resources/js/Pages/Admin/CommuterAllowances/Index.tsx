import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Allowance = {
  id: number;
  user_name: string | null;
  start_date: string | null;
  end_date: string | null;
  from_place: string;
  to_place: string;
  amount: number;
  pass_type: string;
  status: string;
  note: string | null;
  admin_comment: string | null;
  approved_at: string | null;
  approver_name: string | null;
  created_at: string | null;
};

type Props = {
  allowances: Allowance[];
  filters: {
    status: string | null;
  };
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

const statusLabel = (value: string) => {
  switch (value) {
    case 'pending':
      return '申請中';
    case 'approved':
      return '承認済み';
    case 'rejected':
      return '却下';
    case 'active':
      return '有効';
    default:
      return value;
  }
};

export default function Index({ allowances, filters }: Props) {
  const [comments, setComments] = useState<Record<number, string>>({});

  const submitApprove = (id: number) => {
    router.post(route('admin.commuter-allowances.approve', id), {
      admin_comment: comments[id] ?? '',
    });
  };

  const submitReject = (id: number) => {
    router.post(route('admin.commuter-allowances.reject', id), {
      admin_comment: comments[id] ?? '',
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="通勤定期申請管理" />

      <div className="mx-auto max-w-7xl p-6">
        <div className="mb-6">
          <h1 className="text-2xl font-bold">通勤定期申請管理</h1>
        </div>

        <div className="mb-4 flex gap-2">
          <button
            className={`rounded px-4 py-2 ${!filters.status ? 'bg-blue-600 text-white' : 'border'}`}
            onClick={() => router.get(route('admin.commuter-allowances.index'))}
          >
            すべて
          </button>
          <button
            className={`rounded px-4 py-2 ${filters.status === 'pending' ? 'bg-blue-600 text-white' : 'border'}`}
            onClick={() => router.get(route('admin.commuter-allowances.index'), { status: 'pending' })}
          >
            申請中
          </button>
          <button
            className={`rounded px-4 py-2 ${filters.status === 'approved' ? 'bg-blue-600 text-white' : 'border'}`}
            onClick={() => router.get(route('admin.commuter-allowances.index'), { status: 'approved' })}
          >
            承認済み
          </button>
          <button
            className={`rounded px-4 py-2 ${filters.status === 'rejected' ? 'bg-blue-600 text-white' : 'border'}`}
            onClick={() => router.get(route('admin.commuter-allowances.index'), { status: 'rejected' })}
          >
            却下
          </button>
        </div>

        <div className="overflow-x-auto rounded border bg-white">
          <table className="min-w-full border-collapse">
            <thead className="bg-gray-100">
              <tr>
                <th className="border px-4 py-2 text-left">申請者</th>
                <th className="border px-4 py-2 text-left">区間</th>
                <th className="border px-4 py-2 text-left">適用期間</th>
                <th className="border px-4 py-2 text-right">金額</th>
                <th className="border px-4 py-2 text-left">種別</th>
                <th className="border px-4 py-2 text-left">状態</th>
                <th className="border px-4 py-2 text-left">申請備考</th>
                <th className="border px-4 py-2 text-left">管理者コメント</th>
                <th className="border px-4 py-2 text-left">承認情報</th>
                <th className="border px-4 py-2 text-left">操作</th>
              </tr>
            </thead>
            <tbody>
              {allowances.length === 0 ? (
                <tr>
                  <td colSpan={10} className="border px-4 py-6 text-center text-gray-500">
                    通勤定期申請はありません。
                  </td>
                </tr>
              ) : (
                allowances.map((allowance) => (
                  <tr key={allowance.id} className="align-top">
                    <td className="border px-4 py-2">{allowance.user_name ?? '-'}</td>
                    <td className="border px-4 py-2">
                      {allowance.from_place} ～ {allowance.to_place}
                    </td>
                    <td className="border px-4 py-2">
                      {allowance.start_date ?? '-'} ～ {allowance.end_date ?? '-'}
                    </td>
                    <td className="border px-4 py-2 text-right">{allowance.amount.toLocaleString()}円</td>
                    <td className="border px-4 py-2">{passTypeLabel(allowance.pass_type)}</td>
                    <td className="border px-4 py-2">{statusLabel(allowance.status)}</td>
                    <td className="border px-4 py-2">{allowance.note ?? '-'}</td>
                    <td className="border px-4 py-2">
                      <textarea
                        value={comments[allowance.id] ?? allowance.admin_comment ?? ''}
                        onChange={(e) =>
                          setComments((prev) => ({
                            ...prev,
                            [allowance.id]: e.target.value,
                          }))
                        }
                        className="min-h-[80px] w-64 rounded border px-2 py-1"
                      />
                    </td>
                    <td className="border px-4 py-2 text-sm">
                      <div>承認者: {allowance.approver_name ?? '-'}</div>
                      <div>承認日時: {allowance.approved_at ?? '-'}</div>
                    </td>
                    <td className="border px-4 py-2">
                      <div className="flex flex-col gap-2">
                        <button
                          type="button"
                          onClick={() => submitApprove(allowance.id)}
                          className="rounded bg-green-600 px-3 py-2 text-white hover:bg-green-700"
                        >
                          承認
                        </button>
                        <button
                          type="button"
                          onClick={() => submitReject(allowance.id)}
                          className="rounded bg-red-600 px-3 py-2 text-white hover:bg-red-700"
                        >
                          却下
                        </button>
                      </div>
                    </td>
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