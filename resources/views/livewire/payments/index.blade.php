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
                    <div class="flex items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                        <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] text-[17px]" style="background:{{ $meta[1] }};color:{{ $meta[2] }}">{{ $meta[0] }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[13.5px] font-bold text-[#18181b]">{{ $meta[3] }}</div>
                            <div class="truncate text-[11.5px] text-[#a1a1aa]"><x-jdate :value="$p->payment_date" />@if($meta[4]) · {{ $meta[4] }}@endif</div>
                        </div>
                        <div class="text-[14px] font-extrabold" style="color:{{ $meta[2] }}">{{ $outflow ? '−' : '+' }}{{ Fmt::money($p->amount) }}</div>
                    </div>
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
                    <select wire:model="unit_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
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
                    <select wire:model="expense_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="">انتخاب هزینه…</option>
                        @foreach($expenses as $ex)<option value="{{ $ex->id }}">{{ $ex->title }} — {{ Fmt::money($ex->amount) }}</option>@endforeach
                    </select>
                    @error('expense_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
                </label>
            @endif

            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="amount" label="مبلغ ({{ \App\Support\Fmt::currency() }})" type="number" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model="payment_date" label="تاریخ" /></div>
            </div>
            <x-input wire:model="tracking_number" label="شماره پیگیری (اختیاری)" />

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">تصویر رسید (اختیاری)</span>
                <input type="file" wire:model="receipt" accept="image/*,application/pdf"
                       class="rounded-[11px] border border-dashed border-[#d4d4d8] bg-[#fafafa] px-3 py-3 text-[12px] text-[#71717a] file:ml-2 file:rounded-lg file:border-0 file:bg-[#eef0fb] file:px-3 file:py-1.5 file:text-[#5b5bd6]">
                <span wire:loading wire:target="receipt" class="text-[11px] text-[#a1a1aa]">در حال بارگذاری…</span>
                @error('receipt')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>

            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت پرداخت</button>
        </form>
    </x-sheet>
</div>
