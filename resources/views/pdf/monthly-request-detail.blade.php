<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>月次申請詳細</title>

    @php
        $fontRegular = 'file://' . storage_path('fonts/NotoSansJP-Regular.ttf');
        $fontBold = 'file://' . storage_path('fonts/NotoSansJP-Bold.ttf');

        $fmtMinutes = function ($m) {
            $m = (int) $m;
            $h = intdiv($m, 60);
            $min = $m % 60;
            return "{$h}時間{$min}分";
        };

        $fmtDateTime = function ($v) {
            if (empty($v)) {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($v)->format('Y-m-d H:i');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        $fmtWorkDate = function ($v) {
            if (empty($v)) {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        $fmtClock = function ($v) {
            if (empty($v)) {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($v)->format('H:i');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        $summary = $summary ?? [];
        $submittedAt = $submittedAt ?? null;
        $approvedAt = $approvedAt ?? null;
        $approverName = $approverName ?? null;
        $remarks = $remarks ?? null;
        $userName = $userName ?? ($user->name ?? '—');

        $statusMap = [
            'draft' => '下書き',
            'submitted' => '申請中',
            'approved' => '承認済み',
            'rejected' => '差戻し',
        ];

        $requestStatusLabel = $statusMap[$requestStatus ?? ''] ?? ($requestStatus ?? '下書き');
    @endphp

    <style>
        @font-face {
            font-family: 'NotoSansJP';
            font-style: normal;
            font-weight: 400;
            src: url("{{ $fontRegular }}") format("truetype");
        }

        @font-face {
            font-family: 'NotoSansJP';
            font-style: normal;
            font-weight: 700;
            src: url("{{ $fontBold }}") format("truetype");
        }

        * {
            box-sizing: border-box;
        }

        body, table, th, td, div, p, h1, h2, span {
            font-family: 'NotoSansJP', sans-serif;
            color: #111;
        }

        body {
            font-size: 11px;
            line-height: 1.4;
            margin: 18px 20px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 12px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #222;
        }

        .section {
            margin-top: 16px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .box,
        .summary-table,
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .box th,
        .box td,
        .summary-table th,
        .summary-table td,
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #333;
        }

        .box th,
        .summary-table th,
        .attendance-table th {
            background: #f3f3f3;
            font-weight: 700;
        }

        .box th,
        .box td {
            padding: 7px 10px;
            vertical-align: middle;
            word-break: break-word;
        }

        .box th {
            width: 12%;
            text-align: left;
        }

        .box td {
            width: 21%;
        }

        .summary-table th,
        .summary-table td {
            padding: 8px 10px;
            vertical-align: middle;
            word-break: break-word;
        }

        .summary-table th {
            width: 18%;
            text-align: left;
        }

        .summary-table td {
            width: 32%;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 5px 4px;
            vertical-align: middle;
            word-break: break-word;
            line-height: 1.2;
            font-size: 10px;
        }

        .attendance-table th {
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .remarks-box {
            min-height: 56px;
            border: 1px solid #333;
            padding: 8px 10px;
            white-space: pre-wrap;
            font-size: 11px;
        }

        .small {
            font-size: 9px;
            color: #444;
            margin-top: 8px;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <h1 class="page-title">月次申請詳細</h1>

    <div class="section">
        <table class="box">
            <tr>
                <th>対象者</th>
                <td>{{ $userName }}</td>
                <th>対象年月</th>
                <td>{{ $year }}年{{ $month }}月</td>
                <th>状態</th>
                <td>{{ $requestStatusLabel }}</td>
            </tr>
            <tr>
                <th>提出日時</th>
                <td>{{ $fmtDateTime($submittedAt) }}</td>
                <th>承認日時</th>
                <td>{{ $fmtDateTime($approvedAt) }}</td>
                <th>承認者</th>
                <td>{{ $approverName ?: '—' }}</td>
            </tr>
            <tr>
                <th>出力日時</th>
                <td colspan="5">{{ now()->format('Y-m-d H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">月次サマリー</h2>
        <table class="summary-table">
            <tr>
                <th>出勤日数</th>
                <td>{{ data_get($summary, 'working_days', 0) }}日</td>
                <th>所定労働時間</th>
                <td>{{ $fmtMinutes(data_get($summary, 'scheduled_minutes', 0)) }}</td>
            </tr>
            <tr>
                <th>実労働時間</th>
                <td>{{ $fmtMinutes(data_get($summary, 'actual_work_minutes', 0)) }}</td>
                <th>所定内残業</th>
                <td>{{ $fmtMinutes(data_get($summary, 'overtime_in_minutes', 0)) }}</td>
            </tr>
            <tr>
                <th>所定外残業</th>
                <td>{{ $fmtMinutes(data_get($summary, 'overtime_out_minutes', 0)) }}</td>
                <th>深夜時間</th>
                <td>{{ $fmtMinutes(data_get($summary, 'night_minutes', 0)) }}</td>
            </tr>
            <tr>
                <th>遅刻回数</th>
                <td>{{ data_get($summary, 'late_count', 0) }}回</td>
                <th>早退回数</th>
                <td>{{ data_get($summary, 'early_leave_count', 0) }}回</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">勤務一覧</h2>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 12%;">日付</th>
                    <th style="width: 8%;">出勤</th>
                    <th style="width: 8%;">退勤</th>
                    <th style="width: 11%;">実労働</th>
                    <th style="width: 11%;">所定内残業</th>
                    <th style="width: 11%;">所定外残業</th>
                    <th style="width: 10%;">深夜</th>
                    <th style="width: 7%;">遅刻</th>
                    <th style="width: 7%;">早退</th>
                    <th style="width: 15%;">備考</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $row)
                    @php
                        $clockOut = data_get($row, 'clock_out');
                        $totalWorkMinutes = (int) data_get($row, 'minutes.total_work_minutes', 0);
                        $overtimeIn = (int) data_get($row, 'minutes.overtime.in', 0);
                        $overtimeOut = (int) data_get($row, 'minutes.overtime.out', 0);
                        $night = (int) data_get($row, 'minutes.night', 0);

                        $lateLabel = data_get($row, 'is_late') ? '○' : '—';
                        $earlyLeaveLabel = data_get($row, 'is_early_leave') ? '○' : '—';
                        $note = data_get($row, 'note') ?: data_get($row, 'remarks') ?: '—';
                    @endphp
                    <tr>
                        <td>{{ $fmtWorkDate(data_get($row, 'work_date')) }}</td>
                        <td class="text-center">{{ $fmtClock(data_get($row, 'clock_in')) }}</td>
                        <td class="text-center">{{ $fmtClock($clockOut) }}</td>
                        <td class="text-right">{{ $clockOut ? $fmtMinutes($totalWorkMinutes) : '—' }}</td>
                        <td class="text-right">{{ $clockOut ? $fmtMinutes($overtimeIn) : '—' }}</td>
                        <td class="text-right">{{ $clockOut ? $fmtMinutes($overtimeOut) : '—' }}</td>
                        <td class="text-right">{{ $clockOut ? $fmtMinutes($night) : '—' }}</td>
                        <td class="text-center">{{ $lateLabel }}</td>
                        <td class="text-center">{{ $earlyLeaveLabel }}</td>
                        <td>{{ $note }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">この月の勤怠データはありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!empty($remarks))
        <div class="section">
            <h2 class="section-title">備考</h2>
            <div class="remarks-box">{{ $remarks }}</div>
        </div>
    @endif

    <p class="small">※ 本帳票は月次申請内容の確認用PDFです。</p>
</body>
</html>