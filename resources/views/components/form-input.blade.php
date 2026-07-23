@props(['label' => '', 'type' => 'text', 'placeholder' => '', 'required' => false])

@php $id = 'input_' . \Illuminate\Support\Str::random(6); @endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->class([
            'w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all',
            'ltr text-left' => in_array($type, ['number', 'tel', 'email']),
        ]) }}
        {{ $required ? 'required' : '' }}
    />
    @if($attributes->has('wire:model'))
        @php
            $field = $attributes->get('wire:model');
            $field = str_replace(['wire:model=', '"', "'"], '', $field);
        @endphp
        @error($attributes->get('wire:model'))
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    @endif
</div>
