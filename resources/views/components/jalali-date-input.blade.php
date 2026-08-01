@props(['label' => '', 'required' => false])

@php $model = $attributes->wire('model')->value(); @endphp

<div class="flex flex-col gap-1.5">
    @if($label)
        <span class="text-[12.5px] font-semibold text-[#3f3f46]">{{ $label }}@if($required) <span class="text-[#dc2626]">*</span>@endif</span>
    @endif

    <div class="relative" x-data="{ ...window.jalaliPicker(), val: @entangle($model) }" @keydown.escape="open = false">
        {{-- Trigger --}}
        <button type="button" @click="open = !open"
                class="flex h-[46px] w-full items-center justify-between rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]"
                :class="open ? 'border-[#5b5bd6]' : ''">
            <span x-text="label() || 'انتخاب تاریخ'" :class="label() ? 'text-[#18181b]' : 'text-[#a1a1aa]'"></span>
            <svg class="h-4 w-4 text-[#a1a1aa]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </button>

        {{-- Popup calendar --}}
        <div x-show="open" x-cloak x-transition.origin.top
             @click.outside="open = false"
             class="absolute right-0 z-[95] mt-1.5 w-[280px] rounded-2xl border border-[#ececef] bg-white p-3 shadow-[0_16px_40px_-12px_rgba(20,20,30,.3)]"
             style="display:none">
            {{-- Header --}}
            <div class="mb-2 flex items-center justify-between">
                <button type="button" @click="nextMonth()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#71717a] hover:bg-[#f4f4f5]">‹</button>
                <span class="text-[13.5px] font-bold text-[#18181b]" x-text="heading()"></span>
                <button type="button" @click="prevMonth()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#71717a] hover:bg-[#f4f4f5]">›</button>
            </div>
            {{-- Weekday row --}}
            <div class="grid grid-cols-7 gap-1 pb-1">
                <template x-for="w in weekdays" :key="w">
                    <div class="flex h-7 items-center justify-center text-[11px] font-semibold text-[#a1a1aa]" x-text="w"></div>
                </template>
            </div>
            {{-- Day grid --}}
            <template x-for="(row, ri) in weeks()" :key="ri">
                <div class="grid grid-cols-7 gap-1">
                    <template x-for="(d, ci) in row" :key="ci">
                        <button type="button" x-show="true" @click="pick(d)" :disabled="!d"
                                class="flex h-8 items-center justify-center rounded-lg text-[12.5px] transition"
                                :class="d ? (isSelected(d) ? 'bg-[#5b5bd6] font-bold text-white' : (isToday(d) ? 'font-bold text-[#5b5bd6] ring-1 ring-[#5b5bd6]/40' : 'text-[#3f3f46] hover:bg-[#f4f4f5]')) : 'cursor-default'"
                                x-text="d ? fa(d) : ''"></button>
                    </template>
                </div>
            </template>
            {{-- Footer --}}
            <div class="mt-2 flex gap-2 border-t border-[#f4f4f5] pt-2">
                <button type="button" @click="goToday()" class="flex-1 rounded-lg bg-[#eef0fb] py-1.5 text-[12px] font-bold text-[#5b5bd6]">امروز</button>
                <button type="button" @click="clearDate()" class="flex-1 rounded-lg bg-[#f4f4f5] py-1.5 text-[12px] font-bold text-[#71717a]">پاک کردن</button>
            </div>
        </div>
    </div>

    @error($model)<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
</div>
