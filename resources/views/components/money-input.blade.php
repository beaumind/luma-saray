@props(['label' => ''])

@php $model = $attributes->wire('model')->value(); @endphp

<label class="flex flex-col gap-1.5">
    @if($label)<span class="text-[12.5px] font-semibold text-[#3f3f46]">{{ $label }}</span>@endif
    <div x-data="{ raw: @entangle($model) }">
        <input
            type="text" inputmode="numeric" dir="ltr" placeholder="۰"
            x-effect="if (document.activeElement !== $el) $el.value = (raw !== '' && raw != null) ? Number(String(raw).replace(/[^0-9]/g,'')||0).toLocaleString('en-US') : ''"
            @input="raw = $el.value.replace(/[^0-9]/g,''); $el.value = raw ? Number(raw).toLocaleString('en-US') : ''"
            {{ $attributes->except('wire:model')->class([
                'h-[46px] w-full rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-left text-[15px] text-[#18181b] outline-none focus:border-[#5b5bd6]',
            ]) }}
        />
    </div>
    @error($model)<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
</label>
