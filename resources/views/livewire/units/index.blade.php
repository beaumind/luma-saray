@php use App\Support\Fmt; @endphp
<div>
    <x-app-header title="واحدها" :subtitle="Fmt::fa($units->total()).' واحد'">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>افزودن
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        {{-- Filter tabs --}}
        <div class="mb-3.5 flex gap-2">
            @foreach(['all' => 'همه', 'occupied' => 'سکونت', 'empty' => 'خالی'] as $key => $label)
                @php $on = $filter === $key; @endphp
                <button wire:click="setFilter('{{ $key }}')" type="button"
                        class="h-9 flex-1 rounded-[10px] border text-[12.5px] font-semibold {{ $on ? 'border-[#5b5bd6] bg-[#5b5bd6] text-white' : 'border-[#ececef] bg-white text-[#71717a]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Unit cards --}}
        <div class="flex flex-col gap-[9px]">
            @forelse($units as $u)
                @php
                    $bal = $u->balance;
                    $occupied = $u->activeResidents->isNotEmpty();
                    $resident = $u->activeResidents->first();
                    if ($bal > 0) { $balColor = '#dc2626'; $balLabel = Fmt::money($bal); }
                    elseif ($bal < 0) { $balColor = '#16a34a'; $balLabel = Fmt::money($bal); }
                    else { $balColor = '#71717a'; $balLabel = 'تسویه'; }
                @endphp
                <a href="{{ route('units.show', $u->id) }}" wire:navigate
                   class="flex w-full items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px] text-right">
                    <div class="flex h-11 w-11 flex-none items-center justify-center rounded-[12px] text-[15px] font-extrabold"
                         style="background:{{ $occupied ? '#eef0fb' : '#f4f4f5' }};color:{{ $occupied ? '#5b5bd6' : '#a1a1aa' }}">{{ Fmt::fa($u->number) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[14px] font-bold text-[#18181b]">واحد {{ Fmt::fa($u->number) }} · طبقه {{ Fmt::fa($u->floor) }}</div>
                        <div class="mt-0.5 text-[12px] text-[#71717a]">{{ $resident?->name ?? 'بدون ساکن' }} · {{ $resident ? ($resident->type === 'owner' ? 'مالک' : 'مستأجر') : '—' }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 text-left">
                        <span class="rounded-full px-2 py-[3px] text-[10.5px] font-bold"
                              style="background:{{ $occupied ? '#e9f7ef' : '#f4f4f5' }};color:{{ $occupied ? '#16a34a' : '#a1a1aa' }}">{{ $occupied ? 'سکونت' : 'خالی' }}</span>
                        <span class="text-[12.5px] font-bold" style="color:{{ $balColor }}">{{ $balLabel }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">واحدی یافت نشد</div>
            @endforelse
        </div>

        @if($units->hasPages())
            <div class="mt-4">{{ $units->links() }}</div>
        @endif
    </div>

    {{-- Add / edit sheet --}}
    <x-sheet model="showModal" :title="$editingId ? 'ویرایش واحد' : 'افزودن واحد'">
        <form wire:submit="save" class="flex flex-col gap-3">
            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">ساختمان</span>
                <select wire:model="unit_building_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">انتخاب ساختمان…</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
                @error('unit_building_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="number" label="شماره واحد" /></div>
                <div class="flex-1"><x-input wire:model="floor" label="طبقه" type="number" /></div>
            </div>
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="area" label="متراژ" type="number" /></div>
                <div class="flex-1"><x-input wire:model="bedrooms" label="خواب" type="number" /></div>
            </div>
            <div class="flex gap-2.5">
                <div class="flex-1"><x-input wire:model="parking_count" label="پارکینگ" type="number" /></div>
                <div class="flex-1"><x-input wire:model="storage_count" label="انباری" type="number" /></div>
            </div>
            <button type="submit" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ذخیره واحد</button>
        </form>
    </x-sheet>
</div>
