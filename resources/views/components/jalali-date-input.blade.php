@props(['label' => '', 'required' => false, 'min' => null, 'max' => null])

@php
    $model = $attributes->wire('model')->value();
    $id = 'jdp_' . \Illuminate\Support\Str::random(6);
@endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    {{-- wire:ignore protects the picker DOM from Livewire morphing; Alpine + @entangle keeps the value in sync both ways --}}
    <div wire:ignore x-data="{ value: @entangle($model) }" x-init="
        $refs.input.value = value ?? '';
        $refs.input.addEventListener('change', () => value = $refs.input.value);
        $watch('value', v => { if (($refs.input.value ?? '') !== (v ?? '')) $refs.input.value = v ?? ''; });
    ">
        <input
            x-ref="input"
            id="{{ $id }}"
            type="text"
            data-jdp
            data-jdp-only-date
            @if($min) data-jdp-min="{{ $min }}" @endif
            @if($max) data-jdp-max="{{ $max }}" @endif
            readonly
            placeholder="۱۴۰۳/۰۵/۰۱"
            autocomplete="off"
            {{ $attributes->except('wire:model')->class([
                'w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all',
            ]) }}
        />
    </div>

    @error($model)
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
