@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="ساکنان" :back="route('dashboard')" :subtitle="Fmt::fa($residents->total()).' ساکن'">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>افزودن
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        <div class="flex flex-col gap-[9px]">
            @forelse($residents as $r)
                <div class="flex items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] bg-[#f4f4f5] text-[13px] font-bold text-[#52525b]">{{ Fmt::fa($r->unit->number ?? '—') }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13.5px] font-bold text-[#18181b]">{{ $r->name }}</div>
                        <div class="text-[11.5px] text-[#a1a1aa]" dir="ltr">{{ Fmt::fa($r->mobile) }}</div>
                    </div>
                    <button wire:click="openEdit({{ $r->id }})" type="button" class="rounded-full px-2.5 py-[3px] text-[10.5px] font-bold"
                            style="background:{{ $r->type === 'owner' ? '#eef0fb' : '#fdf3e7' }};color:{{ $r->type === 'owner' ? '#5b5bd6' : '#d97706' }}">{{ $r->type === 'owner' ? 'مالک' : 'مستأجر' }}</button>
                </div>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">ساکنی ثبت نشده است</div>
            @endforelse
        </div>
        @if($residents->hasPages())<div class="mt-4">{{ $residents->links() }}</div>@endif
    </div>

    <x-sheet model="showModal" :title="$editingId ? 'ویرایش ساکن' : 'افزودن ساکن'">
        <form wire:submit="save" class="flex flex-col gap-3">
            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">واحد</span>
                <select wire:model="unit_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">انتخاب واحد…</option>
                    @foreach($units as $u)<option value="{{ $u->id }}">واحد {{ Fmt::fa($u->number) }} — {{ $u->building->name }}</option>@endforeach
                </select>
                @error('unit_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>
            <div class="flex gap-2.5">
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">نوع</span>
                    <select wire:model="type" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="owner">مالک</option><option value="tenant">مستأجر</option>
                    </select>
                </label>
                <div class="flex-1"><x-input wire:model="resident_count" label="تعداد نفرات (۰ = خالی)" type="number" /></div>
            </div>
            <x-input wire:model="name" label="نام و نام خانوادگی" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="mobile" label="موبایل" type="tel" /></div>
                <div class="flex-1"><x-input wire:model="national_code" label="کد ملی" /></div>
            </div>
            <div class="flex gap-2.5">
                <div class="flex-1"><x-jalali-date-input wire:model="move_in_date" label="تاریخ ورود" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model="move_out_date" label="تاریخ خروج" /></div>
            </div>
            <x-submit-button target="save" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ذخیره ساکن</x-submit-button>
        </form>
    </x-sheet>
</div>
