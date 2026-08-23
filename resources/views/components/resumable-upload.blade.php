@props([
    'model',
    'folder' => 'receipts',
    'label' => 'فایل ضمیمه (اختیاری)',
    'accept' => 'image/*,application/pdf',
])

<div class="flex flex-col gap-1.5">
    <span class="text-[12.5px] font-semibold text-[#3f3f46]">{{ $label }}</span>

    <div wire:ignore
         x-data="resumableUpload({
            model: '{{ $model }}',
            folder: '{{ $folder }}',
            accept: '{{ $accept }}',
            target: '{{ route('uploads.chunk') }}',
            baseUrl: '{{ \Illuminate\Support\Facades\Storage::disk('public')->url('__PATH__') }}',
         })"
         :data-uploading="uploading ? '1' : '0'"
         @paste="onPaste($event)">

        {{-- Drop zone / trigger --}}
        <div x-ref="zone"
             @dragover.prevent="dragOver = true"
             @dragleave.prevent="dragOver = false"
             @drop="dragOver = false"
             x-show="!path && !uploading"
             class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[12px] border-2 border-dashed px-3 py-5 text-center transition-colors"
             :class="dragOver ? 'border-[#5b5bd6] bg-[#f6f6fd]' : 'border-[#d4d4d8] bg-[#fafafa]'">
            <div class="text-[22px] leading-none">📎</div>
            <button type="button" x-ref="browse" class="text-[12.5px] font-bold text-[#5b5bd6]">انتخاب فایل</button>
            <div class="text-[11px] text-[#a1a1aa]">فایل را بکشید و رها کنید، یا بچسبانید (paste)</div>
        </div>

        {{-- Uploading loader --}}
        <div x-show="uploading" x-cloak class="rounded-[12px] border border-[#ececef] bg-white px-3.5 py-3">
            <div class="mb-2 flex items-center justify-between">
                <span class="truncate text-[12px] font-semibold text-[#18181b]" x-text="fileName">در حال بارگذاری…</span>
                <span class="text-[12px] font-extrabold tabular-nums text-[#5b5bd6]" x-text="progress + '٪'"></span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-[#eef0fb]">
                <div class="h-full rounded-full bg-[#5b5bd6] transition-all duration-200" :style="`width:${progress}%`"></div>
            </div>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-[#a1a1aa]">
                <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-[#c7c7f0] border-t-[#5b5bd6]"></span>
                در حال بارگذاری با قابلیت ادامه…
            </div>
        </div>

        {{-- Uploaded preview --}}
        <div x-show="path && !uploading" x-cloak class="flex items-center gap-3 rounded-[12px] border border-[#e9f7ef] bg-[#f3fbf6] px-3 py-2.5">
            <template x-if="isImage() && previewUrl">
                <img :src="previewUrl" alt="" class="h-11 w-11 flex-none rounded-[9px] border border-[#d1eddd] object-cover">
            </template>
            <template x-if="!isImage() || !previewUrl">
                <div class="flex h-11 w-11 flex-none items-center justify-center rounded-[9px] bg-[#e9f7ef] text-[18px]">📄</div>
            </template>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[12.5px] font-semibold text-[#16a34a]">بارگذاری شد</div>
                <div class="truncate text-[11px] text-[#a1a1aa]" x-text="fileName"></div>
            </div>
            <button type="button" @click="remove()" class="flex-none rounded-[8px] px-2 py-1 text-[11.5px] font-bold text-[#dc2626]">حذف</button>
        </div>

        {{-- Error --}}
        <p x-show="error" x-cloak x-text="error" class="mt-1 text-[11.5px] text-[#dc2626]"></p>
    </div>

    @error($model)<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
</div>
