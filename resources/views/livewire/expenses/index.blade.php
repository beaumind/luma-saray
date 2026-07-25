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
                @php $col = $e->category->color ?? '#5b5bd6'; @endphp
                <div class="flex items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] text-[15px] font-bold" style="background:{{ $col }}1a;color:{{ $col }}">{{ mb_substr($e->category->name ?? 'ه', 0, 1) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[13.5px] font-bold text-[#18181b]">{{ $e->title }}</div>
                        <div class="text-[11.5px] text-[#a1a1aa]">{{ $e->category->name ?? 'بدون دسته' }} · <x-jdate :value="$e->expense_date" /> · {{ ['all_units' => 'همه واحدها', 'selected_units' => 'واحدهای منتخب', 'fund' => 'از صندوق'][$e->distribution] ?? $e->distribution }}</div>
                    </div>
                    <div class="text-[13.5px] font-bold text-[#dc2626]">{{ Fmt::money($e->amount) }}</div>
                </div>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">هزینه‌ای ثبت نشده است</div>
            @endforelse
        </div>
        @if($expenses->hasPages())<div class="mt-4">{{ $expenses->links() }}</div>@endif
    </div>

    <x-sheet model="showModal" title="ثبت هزینه">
        <form wire:submit="save" class="flex flex-col gap-3">
            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">ساختمان</span>
                <select wire:model="building_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
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
            <x-input wire:model="title" label="عنوان" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="amount" label="مبلغ (ریال)" type="number" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model="expense_date" label="تاریخ" /></div>
            </div>
            <div class="flex gap-2.5">
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">توزیع</span>
                    <select wire:model="distribution" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="fund">از صندوق</option><option value="all_units">همه واحدها</option><option value="selected_units">واحدهای منتخب</option>
                    </select>
                </label>
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">مسئول پرداخت</span>
                    <select wire:model="responsible" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="owner">مالک</option><option value="tenant">مستأجر</option><option value="both">هردو</option>
                    </select>
                </label>
            </div>
            <x-input wire:model="description" label="توضیحات" />
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت و توزیع هزینه</button>
        </form>
    </x-sheet>
</div>
