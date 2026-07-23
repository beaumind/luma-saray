<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Filters + action --}}
    <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex items-center gap-3">
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="شماره واحد..."
                    class="pr-9 pl-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] w-48"/>
                <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select wire:model.live="building_id"
                class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                <option value="">همه ساختمان‌ها</option>
                @foreach($buildings as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="openCreate"
            class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            واحد جدید
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">واحد</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ساختمان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ساکن</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">وضعیت مالی</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($units as $unit)
                @php
                    $balance = $unit->balance;
                    $owner = $unit->activeResidents->firstWhere('type', 'owner');
                    $tenant = $unit->activeResidents->firstWhere('type', 'tenant');
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-gray-900">{{ $unit->number }}</div>
                        <div class="text-xs text-gray-400">طبقه {{ $unit->floor }}{{ $unit->area ? ' | ' . $unit->area . ' متر' : '' }}</div>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $unit->building->name }}</td>
                    <td class="px-5 py-3.5">
                        @if($owner)
                            <div class="text-gray-900">{{ $owner->name }}</div>
                            <div class="text-xs text-gray-400">{{ $owner->getTypeLabel() }}</div>
                        @endif
                        @if($tenant)
                            <div class="text-gray-600 text-xs mt-0.5">مستأجر: {{ $tenant->name }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        @if($balance > 0)
                            <span class="inline-flex items-center gap-1 text-red-600 font-semibold text-xs">
                                بدهکار: {{ number_format($balance) }} ت
                            </span>
                        @elseif($balance < 0)
                            <span class="text-blue-600 text-xs font-semibold">اعتبار: {{ number_format(abs($balance)) }} ت</span>
                        @else
                            <span class="text-green-600 text-xs font-medium">تسویه</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('units.show', $unit) }}" class="text-[#0f766e] hover:underline text-xs">مشاهده</a>
                            <button wire:click="openEdit({{ $unit->id }})" class="text-gray-400 hover:text-[#0f766e] text-xs">ویرایش</button>
                            <button wire:click="delete({{ $unit->id }})" wire:confirm="آیا از حذف این واحد مطمئن هستید؟" class="text-gray-400 hover:text-red-500 text-xs">حذف</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-gray-400">هیچ واحدی یافت نشد</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $units->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ $editingId ? 'ویرایش واحد' : 'واحد جدید' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ساختمان <span class="text-red-500">*</span></label>
                    <select wire:model="unit_building_id"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="">انتخاب ساختمان</option>
                        @foreach($buildings as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_building_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-input wire:model="number" label="شماره واحد" placeholder="واحد ۱۰۱" required/>
                    <x-form-input wire:model="floor" label="طبقه" type="number" min="1" required/>
                    <x-form-input wire:model="area" label="متراژ (متر)" type="number" min="1"/>
                    <x-form-input wire:model="bedrooms" label="اتاق خواب" type="number" min="0" required/>
                    <x-form-input wire:model="parking_count" label="پارکینگ" type="number" min="0" required/>
                    <x-form-input wire:model="storage_count" label="انباری" type="number" min="0" required/>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'بروزرسانی' : 'ثبت واحد' }}</span>
                        <span wire:loading>در حال ذخیره...</span>
                    </button>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition-colors">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
