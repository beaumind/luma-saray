@php use App\Support\Fmt; @endphp
<div x-data="{ simple: false, confirmLogout: false }">
    <x-app-header title="تنظیمات" :back="route('dashboard')" />

    <div class="flex flex-col gap-3.5 px-4 pt-4">

        {{-- Simple mode --}}
        <div class="flex items-start gap-3 rounded-[16px] border border-[#ececef] bg-white p-[15px]">
            <div class="flex h-[42px] w-[42px] flex-none items-center justify-center rounded-[12px] bg-[#eef0fb] text-[18px] text-[#5b5bd6]">◐</div>
            <div class="flex-1">
                <div class="text-[14px] font-bold text-[#18181b]">حالت ساده</div>
                <div class="mt-0.5 text-[12px] leading-[1.7] text-[#71717a]">مخصوص ساختمان‌های کوچک. گزارش‌ها و تنظیمات پیشرفته پنهان می‌شوند.</div>
            </div>
            <button @click="simple = !simple" type="button" class="relative mt-0.5 h-[27px] w-[46px] flex-none rounded-full transition"
                    :style="simple ? 'background:#5b5bd6' : 'background:#e4e4e7'">
                <span class="absolute top-[3px] h-[21px] w-[21px] rounded-full bg-white shadow transition-all" :style="simple ? 'right:3px' : 'left:3px'"></span>
            </button>
        </div>

        {{-- Expense categories management --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            <div class="flex items-center justify-between border-b border-[#f4f4f5] px-[15px] py-[13px]">
                <div class="text-[14px] font-bold text-[#18181b]">دسته‌بندی هزینه‌ها</div>
                <button wire:click="openCategoryCreate" type="button" class="rounded-[9px] bg-[#eef0fb] px-3 py-1.5 text-[12px] font-bold text-[#5b5bd6]">＋ افزودن</button>
            </div>
            @forelse($categories as $cat)
                <div class="flex items-center gap-3 border-b border-[#f7f7f8] px-[15px] py-3">
                    <span class="h-[18px] w-[18px] flex-none rounded-[6px]" style="background:{{ $cat->color }}"></span>
                    <span class="flex-1 text-[13.5px] font-semibold text-[#18181b]">{{ $cat->name }}</span>
                    <button wire:click="openCategoryEdit({{ $cat->id }})" type="button" class="text-[12px] font-semibold text-[#5b5bd6]">ویرایش</button>
                    <button wire:click="deleteCategory({{ $cat->id }})" wire:confirm="حذف این دسته؟" type="button" class="text-[12px] font-semibold text-[#dc2626]">حذف</button>
                </div>
            @empty
                <div class="px-[15px] py-6 text-center text-[12.5px] text-[#a1a1aa]">دسته‌ای تعریف نشده است</div>
            @endforelse
        </div>

        {{-- Quick links --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            @php
                $items = [
                    ['icon' => '◱', 'label' => 'ساختمان‌ها', 'value' => '', 'route' => 'buildings.index'],
                    ['icon' => '₪', 'label' => 'قالب‌های شارژ', 'value' => '', 'route' => 'charges.index'],
                    ['icon' => '¤', 'label' => 'واحد پول', 'value' => 'ریال', 'route' => null],
                ];
            @endphp
            @foreach($items as $item)
                @if($item['route'])
                    <a href="{{ route($item['route']) }}" wire:navigate class="flex items-center gap-3 border-b border-[#f7f7f8] px-[15px] py-3.5">
                @else
                    <div class="flex items-center gap-3 border-b border-[#f7f7f8] px-[15px] py-3.5">
                @endif
                    <div class="flex h-8 w-8 flex-none items-center justify-center rounded-[9px] bg-[#f4f4f5] text-[15px]">{{ $item['icon'] }}</div>
                    <span class="flex-1 text-[13.5px] font-semibold text-[#18181b]">{{ $item['label'] }}</span>
                    <span class="text-[12px] text-[#a1a1aa]">{{ $item['value'] }}</span>
                    <span class="text-[16px] text-[#d4d4d8]">‹</span>
                @if($item['route'])</a>@else</div>@endif
            @endforeach
        </div>

        <button @click="confirmLogout = true" type="button" class="h-[46px] rounded-[13px] border border-[#fadada] bg-white text-[14px] font-bold text-[#dc2626]">خروج از حساب</button>
    </div>

    {{-- Category sheet --}}
    <x-sheet model="showCategoryModal" :title="$editingCategoryId ? 'ویرایش دسته' : 'دستهٔ جدید'">
        <form wire:submit="saveCategory" class="flex flex-col gap-3">
            <x-input wire:model="cat_name" label="نام دسته" />
            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">رنگ</span>
                <input type="color" wire:model="cat_color" class="h-[46px] w-full rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-2">
            </label>
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ذخیره دسته</button>
        </form>
    </x-sheet>

    {{-- Logout confirm --}}
    <div x-show="confirmLogout" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-8" style="display:none">
        <div x-show="confirmLogout" x-transition.opacity @click="confirmLogout = false" class="absolute inset-0 bg-[#0a0a0f]/40"></div>
        <div x-show="confirmLogout" x-transition class="relative w-full max-w-[360px] rounded-[20px] bg-white p-[22px] text-center shadow-2xl">
            <div class="mx-auto mb-3.5 flex h-[52px] w-[52px] items-center justify-center rounded-[16px] bg-[#fdeded] text-2xl text-[#dc2626]">!</div>
            <div class="text-[16px] font-extrabold text-[#18181b]">خروج از حساب کاربری</div>
            <div class="mt-1.5 text-[13px] leading-[1.8] text-[#71717a]">آیا مطمئن هستید می‌خواهید خارج شوید؟</div>
            <div class="mt-[18px] flex gap-2.5">
                <button @click="confirmLogout = false" type="button" class="h-11 flex-1 rounded-[12px] border border-[#ececef] bg-white text-[14px] font-bold text-[#3f3f46]">انصراف</button>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="h-11 w-full rounded-[12px] bg-[#dc2626] text-[14px] font-bold text-white">خروج</button>
                </form>
            </div>
        </div>
    </div>
</div>
