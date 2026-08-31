@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="پرداخت‌ها" :subtitle="Fmt::fa($payments->total()).' پرداخت'">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>ثبت
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        {{-- Filters + export --}}
        <div class="mb-3 flex flex-col gap-2.5 rounded-[14px] border border-[#ececef] bg-white p-3">
            <div class="flex gap-2.5">
                <div class="flex-1"><x-jalali-date-input wire:model.live="from" label="از تاریخ" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model.live="to" label="تا تاریخ" /></div>
            </div>
            @if($buildings->count() > 1)
                <select wire:model.live="building_id_filter" class="h-[42px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[13px] outline-none focus:border-[#5b5bd6]">
                    <option value="">همهٔ ساختمان‌ها</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
            @endif
            <div class="flex gap-2">
                <a href="{{ route('payments.export.excel', $exportParams) }}"
                   class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>اکسل</a>
                <a href="{{ route('payments.export.pdf', $exportParams) }}"
                   class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>PDF</a>
                <button type="button" onclick="exportReportImage('#payments-print','payments.png')"
                        class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>تصویر</button>
            </div>
        </div>

        @if($payments->count() === 0)
            <div class="flex flex-col items-center justify-center gap-3.5 px-8 py-[70px] text-center">
                <div class="flex h-[74px] w-[74px] items-center justify-center rounded-[22px] bg-[#f4f4f5] text-3xl text-[#c4c4cc]">₪</div>
                <div>
                    <div class="text-[16px] font-bold text-[#18181b]">هنوز پرداختی ثبت نشده</div>
                    <div class="mt-1.5 text-[13px] leading-[1.8] text-[#a1a1aa]">اولین پرداخت را ثبت کنید</div>
                </div>
                <button wire:click="openCreate" type="button" class="h-11 rounded-xl bg-[#5b5bd6] px-5 text-[14px] font-bold text-white">ثبت پرداخت</button>
            </div>
        @else
            <div class="flex flex-col gap-[9px]">
                @foreach($payments as $p)
                    @php
                        $meta = match($p->type) {
                            'fund_cost' => ['↑', '#fdeded', '#dc2626', 'پرداخت از صندوق', $p->expense?->title],
                            'unit_credit' => ['★', '#eef0fb', '#5b5bd6', 'بستانکاری واحد '.Fmt::fa($p->unit?->number), $p->expense?->title],
                            'unit_cost' => ['↓', '#e9f7ef', '#16a34a', 'هزینهٔ واحد '.Fmt::fa($p->unit?->number), $p->expense?->title],
                            default => ['↓', '#e9f7ef', '#16a34a', 'شارژ واحد '.Fmt::fa($p->unit?->number), null],
                        };
                        $outflow = $p->type === 'fund_cost';
                    @endphp
                    <button wire:click="openDetail({{ $p->id }})" type="button" class="flex w-full items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px] text-right">
                        <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] text-[17px]" style="background:{{ $meta[1] }};color:{{ $meta[2] }}">{{ $meta[0] }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[13.5px] font-bold text-[#18181b]">{{ $meta[3] }}</div>
                            <div class="truncate text-[11.5px] text-[#a1a1aa]"><x-jdate :value="$p->payment_date" />@if($meta[4]) · {{ $meta[4] }}@endif</div>
                        </div>
                        <div class="text-[14px] font-extrabold" style="color:{{ $meta[2] }}">{{ $outflow ? '−' : '+' }}{{ Fmt::money($p->amount) }}</div>
                    </button>
                @endforeach
            </div>
            @if($payments->hasPages())<div class="mt-4">{{ $payments->links() }}</div>@endif
        @endif
    </div>

    {{-- Payment sheet --}}
    <x-sheet model="showModal" title="ثبت پرداخت">
        <form wire:submit="save" class="flex flex-col gap-[13px]">
            {{-- Scenario selector --}}
            <div>
                <span class="mb-1.5 block text-[12.5px] font-semibold text-[#3f3f46]">نوع پرداخت</span>
                <div class="flex flex-col gap-2">
                    @php
                        $types = [
                            'charge' => ['پرداخت شارژ واحد', 'واحد بابت شارژ ماهانه‌اش پرداخت می‌کند'],
                            'fund_cost' => ['پرداخت هزینه از صندوق', 'صندوق یک هزینهٔ ثبت‌شده را پرداخت می‌کند'],
                            'unit_cost' => ['پرداخت هزینه توسط واحد', 'واحد سهم خودش از یک هزینه را می‌پردازد'],
                            'unit_credit' => ['پرداخت هزینه به‌جای صندوق (بستانکاری)', 'واحد هزینه‌ای که با صندوق است را می‌پردازد و بستانکار می‌شود'],
                        ];
                    @endphp
                    @foreach($types as $key => [$t, $d])
                        @php $on = $type === $key; @endphp
                        <button type="button" wire:click="$set('type','{{ $key }}')"
                                class="flex items-start gap-2.5 rounded-[12px] border-[1.5px] px-3 py-2.5 text-right"
                                style="border-color:{{ $on ? '#5b5bd6' : '#ececef' }};background:{{ $on ? '#f6f6fd' : '#fff' }}">
                            <span class="mt-0.5 flex h-[18px] w-[18px] flex-none items-center justify-center rounded-full border-2" style="border-color:{{ $on ? '#5b5bd6' : '#d4d4d8' }}">
                                <span class="h-2 w-2 rounded-full" style="background:{{ $on ? '#5b5bd6' : 'transparent' }}"></span>
                            </span>
                            <span class="flex-1"><span class="block text-[13px] font-bold text-[#18181b]">{{ $t }}</span><span class="mt-0.5 block text-[11.5px] text-[#71717a]">{{ $d }}</span></span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Unit (all except fund_cost) --}}
            @if($type !== 'fund_cost')
                <label class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">واحد</span>
                    <select wire:model.live="unit_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="">انتخاب واحد…</option>
                        @foreach($units as $u)<option value="{{ $u->id }}">واحد {{ Fmt::fa($u->number) }} — {{ $u->building->name }}</option>@endforeach
                    </select>
                    @error('unit_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
                </label>
            @endif

            {{-- Cost (all except charge) --}}
            @if($type !== 'charge')
                <label class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">هزینهٔ مرتبط</span>
                    <select wire:model.live="expense_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="">انتخاب هزینه…</option>
                        @foreach($expenses as $ex)<option value="{{ $ex->id }}">{{ $ex->title }} — {{ Fmt::money($ex->amount) }}</option>@endforeach
                    </select>
                    @error('expense_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
                </label>
            @endif

            <div class="flex gap-2.5">
                <div class="flex-1"><x-money-input wire:model="amount" label="مبلغ ({{ \App\Support\Fmt::currency() }})" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model="payment_date" label="تاریخ" /></div>
            </div>
            <x-input wire:model="tracking_number" label="شماره پیگیری (اختیاری)" />

            @if($type === 'unit_credit')
                <label class="flex cursor-pointer items-start gap-2.5 rounded-[12px] border border-[#e4e4e7] bg-[#fafafa] px-3 py-2.5">
                    <input wire:model="apply_credit_now" type="checkbox" class="mt-0.5 h-5 w-5 flex-none rounded accent-[#5b5bd6]">
                    <span>
                        <span class="block text-[13px] font-semibold text-[#3f3f46]">این مبلغ بلافاصله از بدهی واحد کسر شود</span>
                        <span class="mt-0.5 block text-[11px] leading-5 text-[#a1a1aa]">اگر تیک بزنید، بستانکاری همین حالا با بدهی واحد تهاتر می‌شود. اگر می‌خواهید بعداً نقداً به واحد پرداخت شود، تیک را بردارید.</span>
                    </span>
                </label>
            @endif

            <x-resumable-upload model="receipt_path" folder="receipts" label="تصویر رسید (اختیاری)" />

            <x-submit-button target="save" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت پرداخت</x-submit-button>
        </form>
    </x-sheet>

    {{-- Payment detail --}}
    <x-sheet model="showDetail" title="جزئیات پرداخت">
        @if($detailPayment)
            @php
                $typeLabel = ['charge' => 'پرداخت شارژ', 'fund_cost' => 'پرداخت هزینه از صندوق', 'unit_cost' => 'پرداخت هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد'][$detailPayment->type] ?? $detailPayment->type;
            @endphp
            <div class="flex flex-col gap-3">
                <div class="rounded-[14px] bg-[#f7f7f8] p-3.5 text-center">
                    <div class="text-[12px] text-[#71717a]">{{ $typeLabel }}</div>
                    <div class="mt-1 text-[24px] font-extrabold text-[#18181b]">{{ Fmt::money($detailPayment->amount) }} <span class="text-[13px] font-semibold text-[#71717a]">{{ Fmt::currency() }}</span></div>
                </div>
                @php
                    $rows = array_filter([
                        $detailPayment->unit ? ['واحد', 'واحد '.Fmt::fa($detailPayment->unit->number).' — '.$detailPayment->unit->building?->name] : null,
                        $detailPayment->expense ? ['هزینهٔ مرتبط', $detailPayment->expense->title] : null,
                        ['تاریخ', \App\Support\JDate::toJalali($detailPayment->payment_date)],
                        $detailPayment->tracking_number ? ['شماره پیگیری', Fmt::fa($detailPayment->tracking_number)] : null,
                        $detailPayment->notes ? ['توضیحات', $detailPayment->notes] : null,
                    ]);
                @endphp
                @foreach($rows as [$k, $v])
                    <div class="flex justify-between border-b border-[#f4f4f5] pb-2.5 text-[13px]">
                        <span class="text-[#71717a]">{{ $k }}</span>
                        <span class="font-semibold text-[#18181b]">{{ $v }}</span>
                    </div>
                @endforeach
                @if($detailPayment->receipt_path)
                    <div>
                        <div class="mb-1.5 text-[12.5px] font-semibold text-[#3f3f46]">رسید پرداخت</div>
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($detailPayment->receipt_path) }}" target="_blank">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($detailPayment->receipt_path) }}" alt="رسید"
                                 class="max-h-[280px] w-full rounded-[12px] border border-[#ececef] object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                            <span style="display:none" class="block rounded-[10px] bg-[#fdeded] px-3 py-2 text-center text-[12px] text-[#dc2626]">فایل رسید یافت نشد (احتمالاً پیش از فعال‌سازی ذخیره‌سازی آپلود شده)</span>
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </x-sheet>

    {{-- Off-screen snapshot used by the image export --}}
    @php $ptypeLabels = ['charge' => 'شارژ', 'fund_cost' => 'پرداخت از صندوق', 'unit_cost' => 'هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد']; @endphp
    <div id="payments-print" aria-hidden="true" dir="rtl"
         style="position:absolute;left:-9999px;top:0;width:840px;background:#fff;padding:22px;font-family:Vazirmatn,sans-serif;color:#18181b">
        <div style="text-align:center;font-size:17px;font-weight:800;margin-bottom:4px">گزارش پرداخت‌ها</div>
        <div style="text-align:center;font-size:12px;color:#71717a;margin-bottom:14px">تعداد: {{ Fmt::fa($printRows->count()) }} — مجموع: {{ Fmt::money($printRows->sum('amount')) }} {{ Fmt::currency() }}</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead>
                <tr style="background:#5b5bd6;color:#fff">
                    <th style="border:1px solid #cfcfd6;padding:7px">نوع</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">واحد</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">ساختمان</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">هزینهٔ مرتبط</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">مبلغ</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">تاریخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($printRows as $p)
                    <tr>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $ptypeLabels[$p->type] ?? $p->type }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $p->unit ? Fmt::fa($p->unit->number) : '—' }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $p->unit?->building?->name ?? '—' }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $p->expense?->title ?? '—' }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center;color:#16a34a;font-weight:700">{{ Fmt::money($p->amount) }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center"><x-jdate :value="$p->payment_date" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
