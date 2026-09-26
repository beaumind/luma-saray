@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="داشبورد" :subtitle="$todayLabel" />

    <div class="flex flex-col gap-3.5 px-4 pt-4">

        {{-- Period filter --}}
        <div class="flex flex-col gap-2.5 rounded-[16px] border border-[#ececef] bg-white p-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach(['month' => 'این ماه', 'season' => 'این فصل', 'h1' => 'نیمهٔ اول', 'h2' => 'نیمهٔ دوم', 'year' => 'امسال'] as $key => $label)
                    <button wire:click="setPreset('{{ $key }}')" type="button"
                            class="rounded-full border border-[#ececef] bg-[#fafafa] px-3 py-1 text-[12px] font-semibold text-[#3f3f46] hover:border-[#5b5bd6] hover:text-[#5b5bd6]">{{ $label }}</button>
                @endforeach
            </div>
            <div class="flex gap-2.5">
                <div class="flex-1"><x-jalali-date-input wire:model.live="from" label="از تاریخ" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model.live="to" label="تا تاریخ" /></div>
            </div>
            @if($buildings->count() > 1)
                <select wire:model.live="building_id" class="h-[42px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[13px] outline-none focus:border-[#5b5bd6]">
                    <option value="">همهٔ ساختمان‌ها</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
            @endif
        </div>

        {{-- Balance hero --}}
        <div class="relative overflow-hidden rounded-[18px] bg-gradient-to-br from-[#5b5bd6] to-[#7c6df2] px-[18px] pb-4 pt-[18px] text-white shadow-[0_16px_30px_-14px_rgba(91,91,214,.6)]">
            <div class="absolute -left-8 -top-8 h-[120px] w-[120px] rounded-full bg-white/10"></div>
            <div class="text-[12.5px] font-medium opacity-85">موجودی فعلی صندوق</div>
            <div class="mt-1.5 text-[29px] font-extrabold tracking-tight">{{ Fmt::money($balance) }} <span class="text-[14px] font-semibold opacity-80">{{ Fmt::currency() }}</span></div>
            <div class="mt-3.5 flex gap-2">
                <div class="flex-1 rounded-[11px] bg-white/15 px-[11px] py-[9px]">
                    <div class="text-[11px] opacity-85">مطالبات معوق</div>
                    <div class="mt-[3px] text-[15px] font-bold">{{ Fmt::money($unpaid) }}</div>
                </div>
                <div class="flex-1 rounded-[11px] bg-white/15 px-[11px] py-[9px]">
                    <div class="text-[11px] opacity-85">بستانکاری واحدها</div>
                    <div class="mt-[3px] text-[15px] font-bold">{{ Fmt::money($creditTotal) }}</div>
                </div>
            </div>
        </div>

        {{-- تراز صندوق --}}
        <x-dash-card title="تراز صندوق — دورهٔ انتخابی">
            <x-dash-row label="موجودی ابتدای دوره" :value="Fmt::money($opening)" />
            <x-dash-row label="+ دریافتی (شارژ و سایر)" :value="Fmt::money($received)" color="#16a34a" />
            <x-dash-row label="− پرداخت‌شده از صندوق" :value="Fmt::money($fundOut)" color="#dc2626" />
            <div class="mt-1 flex items-center justify-between rounded-[11px] bg-[#f6f6fd] px-3 py-2.5">
                <span class="text-[13px] font-bold text-[#3f3f46]">= مانده پایان دوره</span>
                <span class="text-[16px] font-extrabold text-[#5b5bd6]">{{ Fmt::money($ending) }} <span class="text-[11px] font-semibold text-[#a1a1aa]">{{ Fmt::currency() }}</span></span>
            </div>
        </x-dash-card>

        {{-- Money flow: inflows / outflow --}}
        <x-dash-card title="گردش وجوه دوره">
            @foreach($inflowRows as $r)
                <x-dash-row :label="$r['label']" :value="'+ '.Fmt::money($r['value'])" :color="$r['color']" />
            @endforeach
            <x-dash-row :label="$fundOutRow['label']" :value="'− '.Fmt::money($fundOutRow['value'])" :color="$fundOutRow['color']" />
        </x-dash-card>

        {{-- Charge collection --}}
        <x-dash-card title="وصول شارژ دوره">
            <div class="mb-2 flex items-end justify-between">
                <div><div class="text-[11px] text-[#71717a]">وصول‌شده از صادرشده</div>
                    <div class="text-[15px] font-bold text-[#18181b]">{{ Fmt::money($chargeCollected) }} <span class="text-[11px] font-normal text-[#a1a1aa]">از {{ Fmt::money($chargesIssued) }}</span></div></div>
                <div class="text-[22px] font-extrabold" style="color:{{ $collectionRate >= 80 ? '#16a34a' : ($collectionRate >= 50 ? '#d97706' : '#dc2626') }}">٪{{ Fmt::fa($collectionRate) }}</div>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                <div class="h-full rounded-full" style="width:{{ $collectionRate }}%;background:{{ $collectionRate >= 80 ? '#16a34a' : ($collectionRate >= 50 ? '#d97706' : '#dc2626') }}"></div>
            </div>
        </x-dash-card>

        {{-- Costs breakdown --}}
        <x-dash-card title="هزینه‌های دوره">
            <div class="grid grid-cols-2 gap-2 pb-1">
                @php
                    $costTiles = [
                        ['کل هزینه‌ها', $expensesTotal, '#18181b'],
                        ['از صندوق (پیش‌بینی‌شده)', $costsFromFund, '#dc2626'],
                        ['پیش‌بینی‌نشده (سهم واحدها)', $costsUnpredicted, '#d97706'],
                        ['بر عهدهٔ مالکین', $costsByOwners, '#5b5bd6'],
                    ];
                @endphp
                @foreach($costTiles as [$l, $v, $c])
                    <div class="rounded-[11px] bg-[#fafafa] px-3 py-2">
                        <div class="text-[11px] text-[#71717a]">{{ $l }}</div>
                        <div class="mt-0.5 text-[14.5px] font-bold" style="color:{{ $c }}">{{ Fmt::money($v) }}</div>
                    </div>
                @endforeach
            </div>
            @if($byCategory->isNotEmpty())
                <div class="mt-2 border-t border-[#f4f4f5] pt-2">
                    <div class="mb-1.5 text-[12px] font-semibold text-[#3f3f46]">به تفکیک دسته‌بندی</div>
                    @foreach($byCategory as $cat)
                        <div class="mb-2">
                            <div class="flex items-center justify-between text-[12px]">
                                <span class="text-[#3f3f46]">{{ $cat['name'] }}</span>
                                <span class="font-semibold text-[#18181b]">{{ Fmt::money($cat['amount']) }} <span class="text-[10.5px] text-[#a1a1aa]">٪{{ Fmt::fa($cat['pct']) }}</span></span>
                            </div>
                            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]"><div class="h-full rounded-full bg-[#5b5bd6]" style="width:{{ $cat['pct'] }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dash-card>

        {{-- Receivables & occupancy stat grid --}}
        <div class="grid grid-cols-2 gap-2.5">
            @php
                $stats = [
                    ['label' => 'واحد بدهکار', 'value' => Fmt::fa($debtorCount).' / '.Fmt::fa($totalUnits), 'sub' => Fmt::money($unpaid).' معوق', 'subColor' => '#dc2626'],
                    ['label' => 'واحد بستانکار', 'value' => Fmt::fa($creditorCount), 'sub' => Fmt::money($creditTotal).' اعتبار', 'subColor' => '#5b5bd6'],
                    ['label' => 'اشغال', 'value' => Fmt::fa($occupied).' / '.Fmt::fa($totalUnits), 'sub' => 'واحد پر', 'subColor' => '#16a34a'],
                    ['label' => 'ساکنان', 'value' => Fmt::fa($residentsTotal), 'sub' => 'نفر', 'subColor' => '#a1a1aa'],
                ];
            @endphp
            @foreach($stats as $s)
                <div class="rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                    <div class="text-[12px] font-medium text-[#71717a]">{{ $s['label'] }}</div>
                    <div class="mt-1 text-[19px] font-extrabold tracking-tight text-[#18181b]">{{ $s['value'] }}</div>
                    <div class="mt-0.5 truncate text-[11px] font-semibold" style="color:{{ $s['subColor'] }}">{{ $s['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Income vs expense chart --}}
        @php $barMax = max(1, collect($bars)->flatMap(fn($b) => [$b['income'], $b['expense']])->max()); @endphp
        <x-dash-card title="درآمد و هزینه (۶ ماه)">
            <div class="mb-1 flex justify-end gap-3 text-[11px] text-[#71717a]">
                <span class="flex items-center gap-1"><span class="h-[9px] w-[9px] rounded-[3px] bg-[#5b5bd6]"></span>درآمد</span>
                <span class="flex items-center gap-1"><span class="h-[9px] w-[9px] rounded-[3px] bg-[#d4d4d8]"></span>هزینه</span>
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
        </x-dash-card>

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
