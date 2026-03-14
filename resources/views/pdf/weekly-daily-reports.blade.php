<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>週間日報</title>

    @php
        $fontRegular = 'file://' . storage_path('fonts/NotoSansJP-Regular.ttf');
        $fontBold = 'file://' . storage_path('fonts/NotoSansJP-Bold.ttf');

        $fmtDate = function ($v) {
            try {
                return \Carbon\Carbon::parse($v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        $weekdayJa = function ($v) {
            try {
                $map = ['日', '月', '火', '水', '木', '金', '土'];
                return $map[\Carbon\Carbon::parse($v)->dayOfWeek] ?? '';
            } catch (\Throwable $e) {
                return '';
            }
        };
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

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body, table, th, td, div, p, h1, h2, span {
            font-family: 'NotoSansJP', sans-serif;
            color: #111;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.5;
        }

        .page {
            page-break-inside: avoid;
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

        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #333;
            padding: 7px 10px;
            vertical-align: middle;
            word-break: break-word;
        }

        .info-table th {
            width: 18%;
            background: #f3f3f3;
            text-align: left;
            font-weight: 700;
        }

        .content-section {
            page-break-inside: avoid;
        }

        .content-box {
            border: 1px solid #333;
            padding: 12px 14px;
            white-space: pre-wrap;
            word-break: break-word;
            min-height: 180mm;
        }

        .small {
            font-size: 9px;
            color: #444;
            margin-top: 8px;
        }
    </style>
</head>
<body>
@foreach ($pages as $page)
    <div class="page">
        <h1 class="page-title">勤務日報</h1>

        <div class="section">
            <table class="info-table">
                <tr>
                    <th>対象者</th>
                    <td>{{ $user->name }}</td>
                    <th>対象日</th>
                    <td>{{ $fmtDate($page['date']) }}（{{ $weekdayJa($page['date']) }}）</td>
                </tr>
                <tr>
                    <th>対象週</th>
                    <td colspan="3">
                        {{ $start->format('Y-m-d') }} ～ {{ $end->format('Y-m-d') }}
                    </td>
                </tr>
                <tr>
                    <th>出力日時</th>
                    <td colspan="3">{{ now()->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
        </div>

        <div class="section content-section">
            <h2 class="section-title">内容</h2>
            <div class="content-box">
                {{ $page['content'] !== '' ? $page['content'] : 'この日の日報はありません。' }}
            </div>
        </div>

        <p class="small">※ 本帳票は1週間分の日報確認用PDFです。</p>
    </div>

    @if (! $loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>