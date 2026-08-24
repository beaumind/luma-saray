@php
    use App\Support\Fmt;
    use App\Support\JDate;
    $dist = ['fund' => 'از صندوق', 'all_units' => 'همه واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; direction: rtl; color: #18181b; font-size: 9px; }
        h2 { text-align: center; font-size: 15px; margin: 0 0 4px; }
        .sum { text-align: center; color: #71717a; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #cfcfd6; padding: 5px 4px; text-align: center; vertical-align: middle; }
        th { background: #5b5bd6; color: #fff; font-size: 9px; }
        td.title { text-align: right; }
        td img { max-width: 60px; max-height: 60px; border-radius: 3px; }
        .amount { color: #dc2626; font-weight: bold; }
        .muted { color: #a1a1aa; font-size: 8px; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="sum">تعداد: {{ Fmt::fa($rows->count()) }} — مجموع مبلغ: {{ Fmt::fa(number_format(Fmt::display((int) $rows->sum('amount')))) }} {{ Fmt::currency() }}</div>

    <table>
        <thead>
            <tr>
                <th style="width:22%">عنوان</th>
                <th>ساختمان</th>
                <th>دسته‌بندی</th>
                <th>تاریخ</th>
                <th>مبلغ ({{ Fmt::currency() }})</th>
                <th>تقسیم</th>
                <th>فاکتور</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $e)
                <tr>
                    <td class="title">{{ $e->title }}</td>
                    <td>{{ $e->building?->name ?? '—' }}</td>
                    <td>{{ $e->category?->name ?? '—' }}</td>
                    <td>{{ JDate::toJalali($e->expense_date) }}</td>
                    <td class="amount">{{ Fmt::fa(number_format(Fmt::display((int) $e->amount))) }}</td>
                    <td>{{ $dist[$e->distribution] ?? $e->distribution }}</td>
                    <td>
                        @php $img = null; @endphp
                        @foreach(($e->attachments ?? []) as $att)
                            @php $img = $img ?: $localPath($att); @endphp
                        @endforeach
                        @if($img)<img src="{{ $img }}">@else—@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">هزینه‌ای در این بازه یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="muted">تاریخ تولید گزارش: {{ JDate::today() }}</div>
</body>
</html>
