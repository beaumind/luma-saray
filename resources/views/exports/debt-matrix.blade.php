<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; direction: rtl; }
        h2 { text-align: center; font-size: 15px; margin: 0 0 10px; color: #18181b; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 0.5px solid #cfcfd6; padding: 5px 4px; text-align: center; }
        th { background: #5b5bd6; color: #fff; font-size: 9.5px; }
        .muted { color: #71717a; font-size: 8px; text-align: center; margin-top: 8px; }
    </style>
</head>
<body>
    <h2>{{ $matrix['title'] }}</h2>
    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($matrix['rows'] as $row)
                <tr>
                    @foreach($columns as $col)
                        @php $fill = $controller->fillHex($row, $col); @endphp
                        <td @if($fill) style="background-color:#{{ $fill }}" @endif>{{ $controller->text($row, $col) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="muted">تاریخ تولید گزارش: {{ \App\Support\JDate::today() }}</div>
</body>
</html>
