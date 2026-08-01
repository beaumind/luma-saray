@php
    use App\Support\Fmt;
    if ($balance > 0) { $balColor = '#dc2626'; $balBg = '#fdeded'; $balState = 'بدهکار'; $balLabel = Fmt::money($balance).' '.Fmt::currency(); }
    elseif ($balance < 0) { $balColor = '#16a34a'; $balBg = '#e9f7ef'; $balState = 'بستانکار'; $balLabel = Fmt::money($balance).' '.Fmt::currency(); }
    else { $balColor = '#71717a'; $balBg = '#f4f4f5'; $balState = 'تسویه'; $balLabel = '۰ '.Fmt::currency(); }
    $occupied = $residents->sum('resident_count') > 0;
@endphp
<div>
    <x-app-header title="واحد {{ Fmt::fa($unit->number) }}" :back="route('units.index')" />

    <div class="flex flex-col gap-3.5 px-4 pt-4">

        {{-- Unit summary --}}
        <div class="rounded-[16px] border border-[#ececef] bg-white p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-[50px] w-[50px] flex-none items-center justify-center rounded-[13px] bg-[#eef0fb] text-[17px] font-extrabold text-[#5b5bd6]">{{ Fmt::fa($unit->number) }}</div>
                <div class="flex-1">
                    <div class="text-[16px] font-extrabold text-[#18181b]">واحد {{ Fmt::fa($unit->number) }}</div>
                    <div class="text-[12.5px] text-[#71717a]">طبقه {{ Fmt::fa($unit->floor) }} · {{ $occupied ? 'ساکن' : 'خالی' }} · {{ Fmt::fa($residents->sum('resident_count')) }} نفر</div>
                </div>
            </div>
            <div class="mt-3.5 flex items-end justify-between border-t border-[#f4f4f5] pt-3.5">
                <div>
                    <div class="text-[12px] text-[#71717a]">مانده حساب</div>
                    <div class="mt-0.5 text-[24px] font-extrabold tracking-tight" style="color:{{ $balColor }}">{{ $balLabel }}</div>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="background:{{ $balBg }};color:{{ $balColor }}">{{ $balState }}</span>
            </div>

            @if($creditBalance > 0)
                <div class="mt-3 flex items-center justify-between rounded-[12px] bg-[#eef0fb] px-3 py-2.5">
                    <div>
                        <div class="text-[11.5px] text-[#5b5bd6]">بستانکاری واحد (پرداختی بابت صندوق)</div>
                        <div class="mt-0.5 text-[16px] font-extrabold text-[#5b5bd6]">{{ Fmt::money($creditBalance) }} {{ Fmt::currency() }}</div>
                    </div>
                    @if($balance > 0)
                        <button wire:click="applyCredit" wire:confirm="بستانکاری این واحد بر بدهی‌اش اعمال شود؟" type="button"
                                class="rounded-[10px] bg-[#5b5bd6] px-3 py-2 text-[12px] font-bold text-white">اعمال بر بدهی</button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Residents --}}
        <div class="flex flex-col gap-[11px] rounded-[16px] border border-[#ececef] bg-white px-[15px] py-3.5">
            <div class="text-[13.5px] font-bold text-[#18181b]">ساکنان</div>
            @forelse($residents as $p)
                <div class="flex items-center gap-2.5">
                    <div class="flex h-[34px] w-[34px] flex-none items-center justify-center rounded-full bg-[#f4f4f5] text-[13px] font-bold text-[#52525b]">{{ mb_substr($p->name, 0, 1) }}</div>
                    <div class="flex-1"><div class="text-[13px] font-semibold text-[#18181b]">{{ $p->name }}</div><div class="text-[11.5px] text-[#a1a1aa]" dir="ltr">{{ Fmt::fa($p->mobile) }}</div></div>
                    <span class="rounded-full px-2.5 py-[3px] text-[10.5px] font-bold" style="background:{{ $p->type === 'owner' ? '#eef0fb' : '#fdf3e7' }};color:{{ $p->type === 'owner' ? '#5b5bd6' : '#d97706' }}">{{ $p->type === 'owner' ? 'مالک' : 'مستأجر' }}</span>
                </div>
            @empty
                <div class="py-2 text-center text-[12.5px] text-[#a1a1aa]">ساکنی ثبت نشده است</div>
            @endforelse
        </div>

        {{-- Ledger (bank statement) --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            <div class="flex items-center justify-between border-b border-[#f4f4f5] px-[15px] py-[13px]">
                <div class="text-[14px] font-bold text-[#18181b]">دفتر مالی واحد</div>
                <span class="text-[11px] text-[#a1a1aa]">صورتحساب</span>
            </div>
            @forelse($ledger as $t)
                @php
                    $isCreditNote = in_array($t['type'], ['credit', 'credit_used']);
                    $dot = match($t['type']) {
                        'payment' => '#16a34a',
                        'cost', 'expense' => '#d97706',
                        'credit', 'credit_used' => '#5b5bd6',
                        default => '#71717a',
                    };
                    $amtColor = $isCreditNote ? '#5b5bd6' : ($t['credit'] ? '#16a34a' : '#dc2626');
                @endphp
                <div class="flex items-center gap-[11px] border-b border-[#f7f7f8] px-[15px] py-3">
                    <div class="h-2 w-2 flex-none rounded-full" style="background:{{ $dot }}"></div>
                    <div class="min-w-0 flex-1"><div class="truncate text-[13px] font-semibold text-[#18181b]">{{ $t['title'] }}</div><div class="text-[11px] text-[#a1a1aa]">{{ $t['date'] }}</div></div>
                    <div class="flex-none text-left">
                        <div class="text-[13.5px] font-bold tabular-nums" style="color:{{ $amtColor }}">{{ $t['credit'] ? '−' : '+' }}{{ Fmt::money($t['amount']) }}</div>
                        <div class="text-[10.5px] tabular-nums text-[#a1a1aa]">بدهی: {{ Fmt::money($t['run']) }}</div>
                    </div>
                </div>
            @empty
                <div class="px-[15px] py-8 text-center text-[13px] text-[#a1a1aa]">تراکنشی ثبت نشده است</div>
            @endforelse
        </div>

        <button wire:click="openPayment" type="button" class="h-12 rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت پرداخت برای این واحد</button>
    </div>

    {{-- Payment sheet --}}
    <x-sheet model="showPaymentModal" title="ثبت پرداخت">
        <form wire:submit="savePayment" class="flex flex-col gap-[13px]">
            <x-input wire:model="pay_amount" label="مبلغ ({{ \App\Support\Fmt::currency() }})" type="number" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-jalali-date-input wire:model="pay_date" label="تاریخ" /></div>
                <div class="flex-1"><x-input wire:model="pay_tracking" label="شماره پیگیری" /></div>
            </div>
            <x-input wire:model="pay_notes" label="توضیحات" />
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت پرداخت</button>
        </form>
    </x-sheet>
</div>
