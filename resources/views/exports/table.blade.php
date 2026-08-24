@php use App\Support\JDate; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; direction: rtl; color: #18181b; font-size: 9px; }
        h2 { text-align: center; font-size: 15px; margin: 0 0 4px; }
        .sum { text-align: center; color: #71717a; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #cfcfd6; padding: 5px 4px; text-align: center; }
        th { background: #5b5bd6; color: #fff; font-size: 9px; }
        .muted { color: #a1a1aa; font-size: 8px; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="sum">تعداد: {{ \App\Support\Fmt::fa($rows->count()) }}</div>
    <table>
        <thead>
            <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach(array_values($row) as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">موردی یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="muted">تاریخ تولید گزارش: {{ JDate::today() }}</div>
</body>
</html>
