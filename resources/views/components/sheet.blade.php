@props(['model', 'title' => ''])

{{-- Bottom sheet bound to a Livewire boolean property named by :model --}}
<div x-data="{ open: @entangle($model) }" x-cloak>
    <div x-show="open" class="fixed inset-0 z-[70]" style="display:none">
        <div x-show="open" x-transition.opacity @click="open = false"
             class="absolute inset-0 bg-[#0a0a0f]/40"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             class="absolute bottom-0 left-1/2 max-h-[90%] w-full max-w-[440px] -translate-x-1/2 overflow-y-auto rounded-t-3xl bg-white px-[18px] pb-7 pt-2.5">
            <div class="mx-auto mb-4 mt-1.5 h-1 w-9 rounded-full bg-[#e4e4e7]"></div>
            @if($title)
                <div class="mb-4 text-[17px] font-extrabold text-[#18181b]">{{ $title }}</div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
