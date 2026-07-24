@props(['label' => '', 'type' => 'text'])

@php $model = $attributes->wire('model')->value(); @endphp

<label class="flex flex-col gap-1.5">
    @if($label)<span class="text-[12.5px] font-semibold text-[#3f3f46]">{{ $label }}</span>@endif
    <input
        type="{{ $type }}"
        {{ $attributes->except('wire:model')->class([
            'h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] text-[#18181b] outline-none focus:border-[#5b5bd6]',
            'text-left' => in_array($type, ['number', 'tel']),
        ]) }}
        @if($model) wire:model="{{ $model }}" @endif
    />
    @if($model)
        @error($model)<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
    @endif
</label>
