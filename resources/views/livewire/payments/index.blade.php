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
                    <div class="mt-1.5 text-[13px] leading-[1.8] text-[#a1a1aa]">اولین پرداخت را ثبت کنید تا در دفتر مالی واحدها اعمال شود</div>
                </div>
                <button wire:click="openCreate" type="button" class="h-11 rounded-xl bg-[#5b5bd6] px-5 text-[14px] font-bold text-white">ثبت پرداخت</button>
            </div>
        @else
            <div class="flex flex-col gap-[9px]">
                @foreach($payments as $p)
                    <div class="flex items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                        <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] bg-[#e9f7ef] text-[17px] text-[#16a34a]">↓</div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[13.5px] font-bold text-[#18181b]">واحد {{ Fmt::fa($p->unit->number ?? '—') }} · {{ $p->unit->activeResidents->first()->name ?? $p->unit->building->name ?? '' }}</div>
                            <div class="text-[11.5px] text-[#a1a1aa]"><x-jdate :value="$p->payment_date" />@if($p->tracking_number) · پیگیری {{ Fmt::fa($p->tracking_number) }}@endif</div>
                        </div>
                        <div class="text-[14px] font-extrabold text-[#16a34a]">{{ Fmt::money($p->amount) }}</div>
                    </div>
                @endforeach
            </div>
            @if($payments->hasPages())<div class="mt-4">{{ $payments->links() }}</div>@endif
        @endif
    </div>

    {{-- Payment sheet --}}
    <x-sheet model="showModal" title="ثبت پرداخت">
        <form wire:submit="save" class="flex flex-col gap-[13px]">
            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">واحد</span>
                <select wire:model="unit_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">انتخاب واحد…</option>
                    @foreach($units as $u)<option value="{{ $u->id }}">واحد {{ Fmt::fa($u->number) }} — {{ $u->building->name }}</option>@endforeach
                </select>
                @error('unit_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>
            <x-input wire:model="amount" label="مبلغ (ریال)" type="number" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-jalali-date-input wire:model="payment_date" label="تاریخ" /></div>
                <div class="flex-1"><x-input wire:model="tracking_number" label="شماره پیگیری" /></div>
            </div>
            <x-input wire:model="notes" label="توضیحات" />
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت پرداخت</button>
        </form>
    </x-sheet>
</div>
