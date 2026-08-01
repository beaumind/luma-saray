@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="هزینه‌ها" :back="route('dashboard')">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>ثبت هزینه
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        <div class="flex flex-col gap-[9px]">
            @forelse($expenses as $e)
                @php
                    $col = $e->category->color ?? '#5b5bd6';
                    $dist = ['fund' => 'از صندوق', 'all_units' => 'همه واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'][$e->distribution] ?? $e->distribution;
                @endphp
                <div class="flex items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] text-[15px] font-bold" style="background:{{ $col }}1a;color:{{ $col }}">{{ mb_substr($e->category->name ?? 'ه', 0, 1) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[13.5px] font-bold text-[#18181b]">{{ $e->title }}</div>
                        <div class="text-[11.5px] text-[#a1a1aa]">{{ $e->category->name ?? 'بدون دسته' }} · <x-jdate :value="$e->expense_date" /> · {{ $dist }}</div>
                    </div>
                    <div class="text-[13.5px] font-bold text-[#dc2626]">{{ Fmt::money($e->amount) }}</div>
                </div>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">هزینه‌ای ثبت نشده است</div>
            @endforelse
        </div>
        @if($expenses->hasPages())<div class="mt-4">{{ $expenses->links() }}</div>@endif
    </div>

    <x-sheet model="showModal" title="ثبت هزینهٔ جدید">
        <form wire:submit="save" class="flex flex-col gap-3">
            <x-input wire:model="title" label="عنوان هزینه" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="amount" label="مبلغ ({{ \App\Support\Fmt::currency() }})" type="number" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model="expense_date" label="تاریخ" /></div>
            </div>

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">ساختمان</span>
                <select wire:model.live="building_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">انتخاب ساختمان…</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
                @error('building_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">دسته‌بندی</span>
                <select wire:model="expense_category_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">بدون دسته</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </label>

            {{-- Funding: who bears this cost --}}
            <div>
                <span class="mb-1.5 block text-[12.5px] font-semibold text-[#3f3f46]">این هزینه بر عهدهٔ کیست؟</span>
                <div class="flex flex-col gap-2">
                    @php
                        $opts = [
                            'fund' => ['شارژ / صندوق ساختمان', 'هزینهٔ پیش‌بینی‌شده که از صندوق پرداخت می‌شود'],
                            'all_units' => ['تقسیم بین همهٔ واحدها', 'هزینهٔ پیش‌بینی‌نشده، سهم هر واحد به بدهی‌اش اضافه می‌شود'],
                            'single_unit' => ['یک واحد مشخص', 'مثل خرابی درب یک واحد — فقط همان واحد بدهکار می‌شود'],
                        ];
                    @endphp
                    @foreach($opts as $key => [$t, $d])
                        @php $on = $distribution === $key; @endphp
                        <button type="button" wire:click="$set('distribution','{{ $key }}')"
                                class="flex items-start gap-2.5 rounded-[12px] border-[1.5px] px-3 py-2.5 text-right"
                                style="border-color:{{ $on ? '#5b5bd6' : '#ececef' }};background:{{ $on ? '#f6f6fd' : '#fff' }}">
                            <span class="mt-0.5 flex h-[18px] w-[18px] flex-none items-center justify-center rounded-full border-2" style="border-color:{{ $on ? '#5b5bd6' : '#d4d4d8' }}">
                                <span class="h-2 w-2 rounded-full" style="background:{{ $on ? '#5b5bd6' : 'transparent' }}"></span>
                            </span>
                            <span class="flex-1"><span class="block text-[13px] font-bold text-[#18181b]">{{ $t }}</span><span class="mt-0.5 block text-[11.5px] text-[#71717a]">{{ $d }}</span></span>
                        </button>
                    @endforeach
                </div>
                @error('distribution')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </div>

            @if($distribution === 'single_unit')
                <label class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">کدام واحد؟</span>
                    <select wire:model="single_unit_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="">انتخاب واحد…</option>
                        @foreach($units as $u)<option value="{{ $u->id }}">واحد {{ Fmt::fa($u->number) }}</option>@endforeach
                    </select>
                    @error('single_unit_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
                </label>
            @endif

            @if($distribution !== 'fund')
                <label class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">مسئول پرداخت</span>
                    <select wire:model="responsible" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="owner">مالک</option><option value="tenant">مستأجر</option><option value="both">هردو</option>
                    </select>
                </label>
            @endif

            <x-input wire:model="description" label="توضیحات (اختیاری)" />

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">تصویر فاکتور (اختیاری)</span>
                <input type="file" wire:model="image" accept="image/*,application/pdf"
                       class="rounded-[11px] border border-dashed border-[#d4d4d8] bg-[#fafafa] px-3 py-3 text-[12px] text-[#71717a] file:ml-2 file:rounded-lg file:border-0 file:bg-[#eef0fb] file:px-3 file:py-1.5 file:text-[#5b5bd6]">
                <span wire:loading wire:target="image" class="text-[11px] text-[#a1a1aa]">در حال بارگذاری…</span>
                @error('image')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>

            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت هزینه</button>
            <p class="text-center text-[11px] text-[#a1a1aa]">پرداخت این هزینه را از بخش «پرداخت‌ها» ثبت کنید.</p>
        </form>
    </x-sheet>
</div>
