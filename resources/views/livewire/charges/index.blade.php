@php
    use App\Support\Fmt;
    $periodLabels = ['monthly' => 'ماهانه', 'quarterly' => 'فصلی', 'yearly' => 'سالانه'];
    $methods = [
        ['id' => 'fixed', 'title' => 'مبلغ ثابت', 'desc' => 'هر واحد مبلغ یکسان پرداخت می‌کند'],
        ['id' => 'per_resident', 'title' => 'به ازای هر نفر', 'desc' => 'بر اساس تعداد ساکنان هر واحد'],
        ['id' => 'combined', 'title' => 'ترکیبی', 'desc' => 'مبلغ ثابت + مبلغ به ازای هر نفر'],
    ];
    $preview = match($type) {
        'fixed' => 'هر واحد مبلغ ثابت پرداخت می‌کند',
        'per_resident' => 'مبلغ به ازای هر نفر × تعداد ساکنان هر واحد',
        default => 'مبلغ ثابت + (مبلغ هر نفر × تعداد نفرات هر واحد)',
    };
@endphp
<div>
    <x-app-header title="شارژها">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>شارژ جدید
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="flex flex-col gap-3.5 px-4 pt-4">
        {{-- Method selector --}}
        <div class="rounded-[16px] border border-[#ececef] bg-white p-[15px]">
            <div class="mb-[11px] text-[13.5px] font-bold text-[#18181b]">روش محاسبهٔ شارژ</div>
            <div class="flex flex-col gap-[9px]">
                @foreach($methods as $m)
                    @php $on = $type === $m['id']; @endphp
                    <button wire:click="setType('{{ $m['id'] }}')" type="button"
                            class="flex items-start gap-[11px] rounded-[13px] border-[1.5px] px-[13px] py-3 text-right"
                            style="border-color:{{ $on ? '#5b5bd6' : '#ececef' }};background:{{ $on ? '#f6f6fd' : '#fff' }}">
                        <span class="mt-0.5 flex h-[19px] w-[19px] flex-none items-center justify-center rounded-full border-2" style="border-color:{{ $on ? '#5b5bd6' : '#d4d4d8' }}">
                            <span class="h-2 w-2 rounded-full" style="background:{{ $on ? '#5b5bd6' : 'transparent' }}"></span>
                        </span>
                        <span class="flex-1"><span class="block text-[13.5px] font-bold text-[#18181b]">{{ $m['title'] }}</span><span class="mt-[3px] block text-[12px] text-[#71717a]">{{ $m['desc'] }}</span></span>
                    </button>
                @endforeach
            </div>
            <div class="mt-3 rounded-[11px] bg-[#f7f7f8] px-[13px] py-3">
                <div class="mb-1 text-[11.5px] text-[#71717a]">پیش‌نمایش محاسبه</div>
                <div class="text-[13px] font-semibold leading-[1.9] text-[#18181b]">{{ $preview }}</div>
            </div>
        </div>

        {{-- Charge templates --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            <div class="border-b border-[#f4f4f5] px-[15px] py-[13px] text-[14px] font-bold text-[#18181b]">دوره‌های شارژ</div>
            @forelse($templates as $c)
                @php
                    $amt = $c->type === 'per_resident' ? Fmt::money($c->per_resident_amount).' / نفر' : Fmt::money($c->fixed_amount);
                @endphp
                <button wire:click="openApply({{ $c->id }})" type="button" class="flex w-full items-center gap-[11px] border-b border-[#f7f7f8] px-[15px] py-3 text-right">
                    <div class="min-w-0 flex-1"><div class="truncate text-[13.5px] font-semibold text-[#18181b]">{{ $c->title }}</div><div class="text-[11.5px] text-[#a1a1aa]">{{ $c->building->name }} · {{ $periodLabels[$c->period] ?? $c->period }}</div></div>
                    <div class="text-left"><div class="text-[13.5px] font-bold text-[#18181b]">{{ $amt }}</div><span class="rounded-full px-2 py-[2px] text-[10.5px] font-bold" style="background:{{ $c->is_active ? '#e9f7ef' : '#f4f4f5' }};color:{{ $c->is_active ? '#16a34a' : '#a1a1aa' }}">{{ $c->is_active ? 'فعال' : 'غیرفعال' }}</span></div>
                </button>
            @empty
                <div class="px-[15px] py-8 text-center text-[13px] text-[#a1a1aa]">قالب شارژی تعریف نشده است</div>
            @endforelse
        </div>

        @if($templates->hasPages())<div>{{ $templates->links() }}</div>@endif
    </div>

    {{-- Create template sheet --}}
    <x-sheet model="showModal" :title="$editingId ? 'ویرایش قالب شارژ' : 'قالب شارژ جدید'">
        <form wire:submit="save" class="flex flex-col gap-3">
            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">ساختمان</span>
                <select wire:model="tpl_building_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">انتخاب ساختمان…</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
                @error('tpl_building_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>
            <x-input wire:model="title" label="عنوان" />
            <div class="flex gap-2.5">
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">روش</span>
                    <select wire:model="type" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="fixed">ثابت</option><option value="per_resident">هر نفر</option><option value="combined">ترکیبی</option>
                    </select>
                </label>
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">دوره</span>
                    <select wire:model="period" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="monthly">ماهانه</option><option value="quarterly">فصلی</option><option value="yearly">سالانه</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="fixed_amount" label="مبلغ ثابت" type="number" /></div>
                <div class="flex-1"><x-input wire:model="per_resident_amount" label="مبلغ هر نفر" type="number" /></div>
            </div>
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ذخیره قالب</button>
        </form>
    </x-sheet>

    {{-- Apply charge sheet --}}
    <x-sheet model="showApplyModal" title="اعمال شارژ">
        <form wire:submit="applyCharge" class="flex flex-col gap-3">
            <x-input wire:model="apply_period" label="دوره (مثال: ۱۴۰۳/۰۵)" />
            <x-jalali-date-input wire:model="apply_date" label="تاریخ ثبت" />
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">اعمال بر همهٔ واحدها</button>
        </form>
    </x-sheet>
</div>
