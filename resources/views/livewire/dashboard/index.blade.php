@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="داشبورد" :subtitle="$todayLabel" />

    <div class="flex flex-col gap-3.5 px-4 pt-4">

        {{-- Balance hero --}}
        <div class="relative overflow-hidden rounded-[18px] bg-gradient-to-br from-[#5b5bd6] to-[#7c6df2] px-[18px] pb-4 pt-[18px] text-white shadow-[0_16px_30px_-14px_rgba(91,91,214,.6)]">
            <div class="absolute -left-8 -top-8 h-[120px] w-[120px] rounded-full bg-white/10"></div>
            <div class="text-[12.5px] font-medium opacity-85">موجودی حساب ساختمان</div>
            <div class="mt-1.5 text-[29px] font-extrabold tracking-tight">{{ Fmt::money($balance) }} <span class="text-[14px] font-semibold opacity-80">{{ \App\Support\Fmt::currency() }}</span></div>
            <div class="mt-3.5 flex gap-2">
                <div class="flex-1 rounded-[11px] bg-white/15 px-[11px] py-[9px]">
                    <div class="text-[11px] opacity-85">مطالبات معوق</div>
                    <div class="mt-[3px] text-[15px] font-bold">{{ Fmt::money($unpaid) }}</div>
                </div>
                <div class="flex-1 rounded-[11px] bg-white/15 px-[11px] py-[9px]">
                    <div class="text-[11px] opacity-85">درآمد این ماه</div>
                    <div class="mt-[3px] text-[15px] font-bold">{{ Fmt::money($monthIncome) }}</div>
                </div>
            </div>
        </div>

        {{-- Stat grid --}}
        <div class="grid grid-cols-2 gap-2.5">
            @php
                $stats = [
                    ['label' => 'کل واحدها', 'value' => Fmt::fa($totalUnits), 'sub' => Fmt::fa($occupied).' سکونت', 'subColor' => '#16a34a'],
                    ['label' => 'ساکنان', 'value' => Fmt::fa($residentsTotal), 'sub' => 'نفر', 'subColor' => '#a1a1aa'],
                    ['label' => 'واحد بدهکار', 'value' => Fmt::fa($debtorCount), 'sub' => 'نیازمند پیگیری', 'subColor' => '#d97706'],
                    ['label' => 'وصولی ماه', 'value' => '٪'.Fmt::fa($collectionRate), 'sub' => 'این ماه', 'subColor' => '#16a34a'],
                ];
            @endphp
            @foreach($stats as $s)
                <div class="rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                    <div class="text-[12px] font-medium text-[#71717a]">{{ $s['label'] }}</div>
                    <div class="mt-1 text-[21px] font-extrabold tracking-tight text-[#18181b]">{{ $s['value'] }}</div>
                    <div class="mt-0.5 text-[11px] font-semibold" style="color:{{ $s['subColor'] }}">{{ $s['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Income vs expense chart --}}
        @php $barMax = max(1, collect($bars)->flatMap(fn($b) => [$b['income'], $b['expense']])->max()); @endphp
        <div class="rounded-[16px] border border-[#ececef] bg-white px-[15px] pb-3 pt-[15px]">
            <div class="mb-0.5 flex items-center justify-between">
                <div class="text-[14px] font-bold text-[#18181b]">درآمد و هزینه</div>
                <div class="flex gap-3 text-[11px] text-[#71717a]">
                    <span class="flex items-center gap-1"><span class="h-[9px] w-[9px] rounded-[3px] bg-[#5b5bd6]"></span>درآمد</span>
                    <span class="flex items-center gap-1"><span class="h-[9px] w-[9px] rounded-[3px] bg-[#d4d4d8]"></span>هزینه</span>
                </div>
            </div>
            <div class="flex h-[118px] items-end gap-0.5 border-b border-[#ececef] pt-1">
                @foreach($bars as $b)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                        <div class="flex flex-1 items-end gap-[3px]">
                            <div class="w-[11px] rounded-t-[3px] bg-[#5b5bd6]" style="height:{{ round($b['income'] / $barMax * 88) }}px"></div>
                            <div class="w-[11px] rounded-t-[3px] bg-[#d4d4d8]" style="height:{{ round($b['expense'] / $barMax * 88) }}px"></div>
                        </div>
                        <span class="text-[10px] text-[#a1a1aa]">{{ $b['m'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Balance trend sparkline --}}
        @php
            $tmax = max(1, max($trend));
            $tmin = min(0, min($trend));
            $span = max(1, $tmax - $tmin);
            $pts = [];
            foreach ($trend as $i => $v) {
                $x = 20 + $i * (280 / 5);
                $y = 82 - (($v - $tmin) / $span) * 66;
                $pts[] = number_format($x, 1, '.', '').','.number_format($y, 1, '.', '');
            }
            $linePts = implode(' ', $pts);
            $areaPath = 'M20,90 L'.implode(' L', $pts).' L300,90 Z';
        @endphp
        <div class="rounded-[16px] border border-[#ececef] bg-white px-[15px] pb-2.5 pt-[15px]">
            <div class="mb-1.5 text-[14px] font-bold text-[#18181b]">روند موجودی (۶ ماه)</div>
            <svg viewBox="0 0 320 96" class="block h-auto w-full">
                <defs><linearGradient id="ga" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5b5bd6" stop-opacity=".18"></stop><stop offset="1" stop-color="#5b5bd6" stop-opacity="0"></stop></linearGradient></defs>
                <path d="{{ $areaPath }}" fill="url(#ga)"></path>
                <polyline points="{{ $linePts }}" fill="none" stroke="#5b5bd6" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"></polyline>
            </svg>
        </div>

        {{-- Top debtors --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            <div class="border-b border-[#f4f4f5] px-[15px] pb-2.5 pt-[13px] text-[14px] font-bold text-[#18181b]">بیشترین بدهی واحدها</div>
            @forelse($debtors as $d)
                <a href="{{ route('units.show', $d['id']) }}" wire:navigate
                   class="flex w-full items-center gap-[11px] border-b border-[#f7f7f8] px-[15px] py-[11px] text-right">
                    <div class="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] bg-[#fdeded] text-[13px] font-bold text-[#dc2626]">{{ Fmt::fa($d['no']) }}</div>
                    <div class="min-w-0 flex-1"><div class="text-[13.5px] font-semibold text-[#18181b]">{{ $d['owner'] }}</div><div class="text-[11.5px] text-[#a1a1aa]">طبقه {{ Fmt::fa($d['floor']) }}</div></div>
                    <div class="text-left"><div class="text-[13.5px] font-bold text-[#dc2626]">{{ Fmt::money($d['amount']) }}</div><div class="text-[11px] text-[#a1a1aa]">بدهکار</div></div>
                </a>
            @empty
                <div class="px-[15px] py-6 text-center text-[12.5px] text-[#a1a1aa]">همهٔ واحدها تسویه هستند 🎉</div>
            @endforelse
        </div>

        {{-- Recent activity --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            <div class="flex items-center justify-between border-b border-[#f4f4f5] px-[15px] pb-2.5 pt-[13px]">
                <div class="text-[14px] font-bold text-[#18181b]">تراکنش‌های اخیر</div>
                <a href="{{ route('payments.index') }}" wire:navigate class="text-[12px] font-semibold text-[#5b5bd6]">همه</a>
            </div>
            @forelse($activity as $a)
                <div class="flex items-center gap-[11px] border-b border-[#f7f7f8] px-[15px] py-[11px]">
                    <div class="flex h-[34px] w-[34px] flex-none items-center justify-center rounded-[10px] text-[15px]" style="background:{{ $a['credit'] ? '#e9f7ef' : '#fdeded' }};color:{{ $a['credit'] ? '#16a34a' : '#dc2626' }}">{{ $a['credit'] ? '↓' : '↑' }}</div>
                    <div class="min-w-0 flex-1"><div class="truncate text-[13px] font-semibold text-[#18181b]">{{ $a['title'] }}</div><div class="text-[11.5px] text-[#a1a1aa]">{{ $a['date'] }}</div></div>
                    <div class="text-[13.5px] font-bold" style="color:{{ $a['credit'] ? '#16a34a' : '#dc2626' }}">{{ Fmt::money($a['amount']) }}</div>
                </div>
            @empty
                <div class="px-[15px] py-6 text-center text-[12.5px] text-[#a1a1aa]">تراکنشی ثبت نشده است</div>
            @endforelse
        </div>

    </div>
</div>
