@props(['title' => ''])

<div class="rounded-[16px] border border-[#ececef] bg-white px-[15px] py-3.5">
    @if($title)<div class="mb-2 text-[14px] font-bold text-[#18181b]">{{ $title }}</div>@endif
    {{ $slot }}
</div>
