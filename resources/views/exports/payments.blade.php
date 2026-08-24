@php
    use App\Support\Fmt;
    use App\Support\JDate;
    $ptype = ['charge' => 'شارژ', 'fund_cost' => 'پرداخت از صندوق', 'unit_cost' => 'هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد'];
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
        td img { max-width: 70px; max-height: 70px; border-radius: 3px; }
        .muted { color: #a1a1aa; font-size: 8px; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="sum">تعداد: {{ Fmt::fa($rows->count()) }} — مجموع مبلغ: {{ Fmt::fa(number_format(Fmt::display((int) $rows->sum('amount')))) }} {{ Fmt::currency() }}</div>

    <table>
        <thead>
            <tr>
                <th>نوع</th><th>واحد</th><th>ساختمان</th><th>هزینهٔ مرتبط</th>
                <th>مبلغ ({{ Fmt::currency() }})</th><th>تاریخ</th><th>پیگیری</th><th>رسید</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $p)
                <tr>
                    <td>{{ $ptype[$p->type] ?? $p->type }}</td>
                    <td>{{ $p->unit ? Fmt::fa($p->unit->number) : '—' }}</td>
                    <td>{{ $p->unit?->building?->name ?? '—' }}</td>
                    <td>{{ $p->expense?->title ?? '—' }}</td>
                    <td>{{ Fmt::fa(number_format(Fmt::display((int) $p->amount))) }}</td>
                    <td>{{ JDate::toJalali($p->payment_date) }}</td>
                    <td>{{ $p->tracking_number ? Fmt::fa($p->tracking_number) : '—' }}</td>
                    <td>@php $img = $localPath($p->receipt_path); @endphp @if($img)<img src="{{ $img }}">@else—@endif</td>
                </tr>
            @empty
                <tr><td colspan="8">پرداختی در این بازه یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="muted">تاریخ تولید گزارش: {{ JDate::today() }}</div>
</body>
</html>
