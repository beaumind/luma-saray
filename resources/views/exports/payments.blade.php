@php
    use App\Support\Fmt;
    use App\Support\JDate;
    $ptype = ['charge' => 'شارژ', 'fund_cost' => 'پرداخت از صندوق', 'unit_cost' => 'هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد'];
    $by = fn ($t) => (int) $rows->where('type', $t)->sum('amount');
    $cnt = fn ($t) => $rows->where('type', $t)->count();
    $toFund = $by('charge') + $by('unit_cost') + $by('deposit');
    $fromFund = $by('fund_cost');
    $credit = $by('unit_credit');
    $net = $toFund - $fromFund;
    $m = fn ($v) => Fmt::fa(number_format(Fmt::display((int) $v)));
    // Per-type rows shown in the summary: [label, amount, count, colour, direction]
    $summaryRows = [
        ['شارژ دریافتی', $by('charge'), $cnt('charge'), '#16a34a', '+'],
        ['هزینهٔ واحد (دریافتی)', $by('unit_cost'), $cnt('unit_cost'), '#16a34a', '+'],
        ['واریز/سایر', $by('deposit'), $cnt('deposit'), '#16a34a', '+'],
        ['بستانکاری واحد', $by('unit_credit'), $cnt('unit_credit'), '#5b5bd6', ''],
        ['پرداخت از صندوق', $by('fund_cost'), $cnt('fund_cost'), '#dc2626', '−'],
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; direction: rtl; color: #18181b; font-size: 9px; }
        h2 { text-align: center; font-size: 15px; margin: 0 0 4px; }
        .sum { text-align: center; font-size: 10px; margin-bottom: 12px; }
        .sum .in { color: #16a34a; }
        .sum .out { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #cfcfd6; padding: 5px 4px; text-align: center; vertical-align: middle; }
        th { background: #5b5bd6; color: #fff; font-size: 9px; }
        .muted { color: #a1a1aa; font-size: 8px; text-align: center; margin-top: 10px; }
        .in { color: #16a34a; font-weight: bold; }
        .out { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>

    {{-- Aggregated summary --}}
    <table style="width:100%;margin-bottom:6px;border-collapse:separate;border-spacing:5px 0">
        <tr>
            <td style="width:33%;border:0.75px solid #bbe7cb;background:#f3fbf6;border-radius:6px;padding:7px;text-align:center">
                <div style="font-size:9px;color:#71717a">مجموع دریافتی به صندوق</div>
                <div style="font-size:13px;font-weight:bold;color:#16a34a">{{ $m($toFund) }}</div>
            </td>
            <td style="width:33%;border:0.75px solid #f0c9c9;background:#fdf3f3;border-radius:6px;padding:7px;text-align:center">
                <div style="font-size:9px;color:#71717a">مجموع پرداختی از صندوق</div>
                <div style="font-size:13px;font-weight:bold;color:#dc2626">{{ $m($fromFund) }}</div>
            </td>
            <td style="width:33%;border:0.75px solid #cfd0f2;background:#f6f6fd;border-radius:6px;padding:7px;text-align:center">
                <div style="font-size:9px;color:#71717a">خالص جریان صندوق</div>
                <div style="font-size:13px;font-weight:bold;color:{{ $net >= 0 ? '#16a34a' : '#dc2626' }}">{{ $net >= 0 ? '+' : '−' }}{{ $m(abs($net)) }}</div>
            </td>
        </tr>
    </table>

    {{-- Per-type breakdown --}}
    <table style="margin-bottom:12px">
        <thead><tr><th>نوع پرداخت</th><th>تعداد</th><th>مبلغ ({{ Fmt::currency() }})</th></tr></thead>
        <tbody>
            @foreach($summaryRows as [$label, $amount, $count, $color, $sign])
                <tr>
                    <td style="text-align:right">{{ $label }}</td>
                    <td>{{ Fmt::fa($count) }}</td>
                    <td style="color:{{ $color }};font-weight:bold">{{ $sign }}{{ $m($amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="muted" style="margin:0 0 6px">— ریز تراکنش‌ها ({{ Fmt::fa($rows->count()) }} مورد) —</div>

    <table>
        <thead>
            <tr>
                <th>نوع</th><th>واحد</th><th>ساختمان</th><th>هزینهٔ مرتبط</th>
                <th>مبلغ ({{ Fmt::currency() }})</th><th>تاریخ</th><th>پیگیری</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $p)
                @php $out = $p->type === 'fund_cost'; @endphp
                <tr>
                    <td>{{ $ptype[$p->type] ?? $p->type }}</td>
                    <td>{{ $p->unit ? Fmt::fa($p->unit->number) : '—' }}</td>
                    <td>{{ $p->unit?->building?->name ?? '—' }}</td>
                    <td>{{ $p->expense?->title ?? '—' }}</td>
                    <td style="color:{{ $out ? '#dc2626' : '#16a34a' }};font-weight:bold">{{ $out ? '−' : '+' }}{{ Fmt::fa(number_format(Fmt::display((int) $p->amount))) }}</td>
                    <td>{{ JDate::toJalali($p->payment_date) }}</td>
                    <td>{{ $p->tracking_number ? Fmt::fa($p->tracking_number) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">پرداختی در این بازه یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="muted">تاریخ تولید گزارش: {{ JDate::today() }}</div>
</body>
</html>
