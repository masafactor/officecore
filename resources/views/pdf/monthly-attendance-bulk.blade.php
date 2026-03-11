<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>勤怠月報</title>
    <style>
   @font-face {
        font-family: 'ipaexg';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path("fonts/ipaexg.ttf") }}') format('truetype');
    }


        body { font-size: 12px; }
        h1 { font-size: 18px;font-family: 'ipag', 'ipaexg', sans-serif; margin-bottom: 12px; }
        p { margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; font-size: 11px; vertical-align: top; }
    </style>
</head>
<body>
    <h1>勤怠月報</h1>
    <p>{{ $year }}年{{ $month }}月</p>

    <table>
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>残業（内/外）</th>
                <th>深夜</th>
                <th>総労働時間</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $row)
                @php
                    @php
    $clockOut = data_get($row, 'clock_out');

    $overtimeIn = (int) data_get($row, 'minutes.overtime.in', 0);
    $overtimeOut = (int) data_get($row, 'minutes.overtime.out', 0);
    $night = (int) data_get($row, 'minutes.night', 0);
    $totalWorkMinutes = (int) data_get($row, 'minutes.total_work_minutes', 0);

    $fmtMinutes = function ($m) {
        $m = (int) $m;
        if ($m <= 0) {
            return '—';
        }
        $h = intdiv($m, 60);
        $min = $m % 60;
        return sprintf('%dh %dm', $h, $min);
    };
@endphp

<tr>
    <td>{{ data_get($row, 'work_date', '—') }}</td>
    <td>{{ data_get($row, 'clock_in', '—') }}</td>
    <td>{{ $clockOut ?: '—' }}</td>

    <td>
        @if(!$clockOut || ($overtimeIn + $overtimeOut) <= 0)
            —
        @else
            @if($overtimeIn > 0)
                内 {{ $fmtMinutes($overtimeIn) }}
            @endif

            @if($overtimeIn > 0 && $overtimeOut > 0)
                <br>
            @endif

            @if($overtimeOut > 0)
                外 {{ $fmtMinutes($overtimeOut) }}
            @endif
        @endif
    </td>

    <td>{{ !$clockOut ? '—' : $fmtMinutes($night) }}</td>
    <td>{{ !$clockOut ? '—' : $fmtMinutes($totalWorkMinutes) }}</td>
</tr>
            @empty
                <tr>
                    <td colspan="6">この月の勤怠データはありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>