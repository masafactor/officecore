<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>日報詳細</title>

    @php
        $fontRegular = 'file://' . storage_path('fonts/NotoSansJP-Regular.ttf');
        $fontBold = 'file://' . storage_path('fonts/NotoSansJP-Bold.ttf');
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

        body {
            font-family: 'NotoSansJP', sans-serif;
            font-size: 12px;
            line-height: 1.7;
            color: #222;
            margin: 0;
            padding: 24px;
            background: #fff;
        }

        .page {
            border: 1px solid #cfd8e3;
        }

        .header {
            background: #dbeafe;
            border-bottom: 1px solid #93c5fd;
            padding: 12px 16px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .section {
            padding: 16px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 10px 0;
            padding-left: 8px;
            border-left: 4px solid #60a5fa;
        }

        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        .info-table th {
            width: 120px;
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        .status {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #9ca3af;
            border-radius: 3px;
            font-size: 11px;
        }

        .content-box {
            border: 1px solid #d1d5db;
            min-height: 220px;
            padding: 12px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .footer {
            padding: 0 16px 16px 16px;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1 class="header-title">日報詳細</h1>
        </div>

        <div class="section">
            <h2 class="section-title">基本情報</h2>

            <table class="info-table">
                <tr>
                    <th>社員名</th>
                    <td>{{ $dailyReport->user->name }}</td>
                </tr>
                <tr>
                    <th>勤務日</th>
                    <td>{{ \Carbon\Carbon::parse($dailyReport->report_date)->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>ステータス</th>
                    <td>
                        <span class="status">{{ $dailyReport->status }}</span>
                    </td>
                </tr>
            </table>

            <h2 class="section-title">内容</h2>
            <div class="content-box">{{ $dailyReport->content }}</div>
        </div>

        <div class="footer">
            出力日時：{{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>