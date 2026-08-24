@php
    use App\Support\Fmt;
    use App\Support\JDate;
    $dist = ['fund' => 'از صندوق', 'all_units' => 'همه واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'];
    $ptype = ['charge' => 'شارژ', 'fund_cost' => 'پرداخت از صندوق', 'unit_cost' => 'هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; direction: rtl; color: #18181b; font-size: 10px; }
        h2 { text-align: center; font-size: 15px; margin: 0 0 4px; }
        .sum { text-align: center; color: #71717a; font-size: 10px; margin-bottom: 12px; }
        .card { border: 0.75px solid #cfcfd6; border-radius: 6px; padding: 8px 10px; margin-bottom: 9px; }
        .row { width: 100%; }
        .title { font-size: 12px; font-weight: bold; }
        .amount { font-size: 12px; font-weight: bold; color: #dc2626; }
        .meta { color: #52525b; font-size: 9px; margin-top: 3px; }
        .desc { color: #3f3f46; font-size: 9px; margin-top: 3px; }
        .att { margin-top: 6px; }
        .att img { max-width: 130px; max-height: 130px; border: 0.5px solid #ddd; border-radius: 4px; }
        .pay { border-top: 0.5px dashed #cfcfd6; margin-top: 7px; padding-top: 6px; }
        .pay-h { font-size: 9px; color: #16a34a; font-weight: bold; margin-bottom: 4px; }
        .pay-item { font-size: 9px; margin-bottom: 5px; }
        .pay-item img { max-width: 100px; max-height: 100px; border: 0.5px solid #ddd; border-radius: 4px; margin-top: 3px; }
        .muted { color: #a1a1aa; font-size: 8px; text-align: center; margin-top: 10px; }
        .lbl { color: #71717a; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="sum">تعداد: {{ Fmt::fa($rows->count()) }} — مجموع مبلغ: {{ Fmt::fa(number_format(Fmt::display((int) $rows->sum('amount')))) }} {{ Fmt::currency() }}</div>

    @forelse($rows as $e)
        <div class="card">
            <table class="row"><tr>
                <td class="title">{{ $e->title }}</td>
                <td class="amount" style="text-align:left">{{ Fmt::fa(number_format(Fmt::display((int) $e->amount))) }} {{ Fmt::currency() }}</td>
            </tr></table>
            <div class="meta">
                <span class="lbl">ساختمان:</span> {{ $e->building?->name ?? '—' }} ·
                <span class="lbl">دسته:</span> {{ $e->category?->name ?? '—' }} ·
                <span class="lbl">تاریخ:</span> {{ JDate::toJalali($e->expense_date) }} ·
                <span class="lbl">تقسیم:</span> {{ $dist[$e->distribution] ?? $e->distribution }}
            </div>
            @if($e->description)<div class="desc">{{ $e->description }}</div>@endif

            @foreach(($e->attachments ?? []) as $att)
                @php $img = $localPath($att); @endphp
                @if($img)<div class="att"><img src="{{ $img }}"></div>@endif
            @endforeach

            @if($e->relatedPayments->isNotEmpty())
                <div class="pay">
                    <div class="pay-h">پرداخت‌های مرتبط ({{ Fmt::fa($e->relatedPayments->count()) }})</div>
                    @foreach($e->relatedPayments as $p)
                        <div class="pay-item">
                            • {{ $ptype[$p->type] ?? $p->type }}@if($p->unit) — واحد {{ Fmt::fa($p->unit->number) }}@endif —
                            {{ Fmt::fa(number_format(Fmt::display((int) $p->amount))) }} {{ Fmt::currency() }} —
                            {{ JDate::toJalali($p->payment_date) }}@if($p->tracking_number) — پیگیری: {{ Fmt::fa($p->tracking_number) }}@endif
                            @php $rimg = $localPath($p->receipt_path); @endphp
                            @if($rimg)<div><img src="{{ $rimg }}"></div>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="sum">هزینه‌ای در این بازه یافت نشد.</div>
    @endforelse

    <div class="muted">تاریخ تولید گزارش: {{ JDate::today() }}</div>
</body>
</html>
