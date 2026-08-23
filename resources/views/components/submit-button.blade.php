@props([
    'target' => 'save',
    'loadingLabel' => 'در حال ثبت…',
])

{{--
  Submit button that:
  - warns if a file in the same form is still uploading (you'd lose it),
  - disables + shows a spinner while the Livewire action runs, so the click
    visibly registers.
--}}
<button type="submit"
        wire:target="{{ $target }}"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-70 cursor-not-allowed"
        @click="
            const up = $el.closest('form')?.querySelector('[data-uploading=\'1\']');
            if (up && !confirm('یک فایل هنوز در حال بارگذاری است. اگر همین حالا ثبت کنید، آن فایل ذخیره نمی‌شود. مطمئن هستید؟')) {
                $event.preventDefault();
                $event.stopImmediatePropagation();
            }
        "
        {{ $attributes->merge(['class' => 'inline-flex items-center justify-center transition-opacity disabled:cursor-not-allowed']) }}>
    <span wire:loading.remove wire:target="{{ $target }}" class="flex items-center justify-center gap-2">{{ $slot }}</span>
    <span wire:loading wire:target="{{ $target }}" class="flex items-center justify-center gap-2">
        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
        {{ $loadingLabel }}
    </span>
</button>
