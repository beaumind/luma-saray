@props(['title' => '', 'subtitle' => null, 'back' => null])

<div class="sticky top-0 z-30 flex min-h-[52px] items-center gap-2.5 border-b border-[#ececef] bg-[#f7f7f8]/90 px-[18px] pb-3 pt-2 backdrop-blur-md">
    @if($back)
        <a href="{{ $back }}" wire:navigate
           class="flex h-[34px] w-[34px] flex-none items-center justify-center rounded-[10px] border border-[#ececef] bg-white text-lg text-[#3f3f46]">‹</a>
    @endif
    <div class="min-w-0 flex-1">
        <div class="truncate text-[18px] font-extrabold tracking-tight text-[#18181b]">{{ $title }}</div>
        @if($subtitle)
            <div class="mt-px text-[12px] text-[#a1a1aa]">{{ $subtitle }}</div>
        @endif
    </div>
    @if(isset($action))
        <div class="flex-none">{{ $action }}</div>
    @endif
</div>
