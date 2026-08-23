@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="ساختمان‌ها" :back="route('dashboard')">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>افزودن
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        <div class="flex flex-col gap-[9px]">
            @forelse($buildings as $b)
                <a href="{{ route('buildings.show', $b->id) }}" wire:navigate class="flex items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px]">
                    <div class="flex h-11 w-11 flex-none items-center justify-center rounded-[12px] bg-[#eef0fb] text-[18px]">🏢</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[14px] font-bold text-[#18181b]">{{ $b->name }}</div>
                        <div class="truncate text-[11.5px] text-[#a1a1aa]">{{ $b->city }} · {{ $b->address }}</div>
                    </div>
                    <div class="text-left"><div class="text-[13px] font-bold text-[#5b5bd6]">{{ Fmt::fa($b->units_count) }} واحد</div><div class="text-[11px] text-[#a1a1aa]">{{ Fmt::fa($b->floors) }} طبقه</div></div>
                </a>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">ساختمانی ثبت نشده است</div>
            @endforelse
        </div>
        @if($buildings->hasPages())<div class="mt-4">{{ $buildings->links() }}</div>@endif
    </div>

    <x-sheet model="showModal" :title="$editingId ? 'ویرایش ساختمان' : 'افزودن ساختمان'">
        <form wire:submit="save" class="flex flex-col gap-3">
            <x-input wire:model="name" label="نام ساختمان" />
            <x-input wire:model="address" label="آدرس" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="city" label="شهر" /></div>
                <div class="flex-1"><x-input wire:model="floors" label="طبقات" type="number" /></div>
            </div>
            <x-input wire:model="total_units" label="تعداد واحدها" type="number" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="manager_name" label="نام مدیر" /></div>
                <div class="flex-1"><x-input wire:model="manager_mobile" label="موبایل مدیر" type="tel" /></div>
            </div>
            <x-submit-button target="save" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ذخیره ساختمان</x-submit-button>
        </form>
    </x-sheet>
</div>
