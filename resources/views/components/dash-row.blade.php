@props(['label' => '', 'value' => '', 'color' => '#18181b'])

<div class="flex items-center justify-between border-b border-[#f4f4f5] py-2 text-[13px] last:border-0">
    <span class="text-[#71717a]">{{ $label }}</span>
    <span class="font-semibold" style="color:{{ $color }}">{{ $value }}</span>
</div>
