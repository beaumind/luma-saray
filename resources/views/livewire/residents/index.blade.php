<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="نام یا موبایل..."
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
            <select wire:model.live="type_filter"
                class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                <option value="">همه نوع‌ها</option>
                <option value="owner">مالک</option>
                <option value="tenant">مستأجر</option>
            </select>
        </div>
        <button wire:click="openCreate"
            class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            ساکن جدید
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">نام</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">نوع</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">واحد</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">موبایل</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">تعداد نفر</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($residents as $resident)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-[#0f766e]/10 flex items-center justify-center text-[#0f766e] font-bold text-xs flex-shrink-0">
                                {{ mb_substr($resident->name, 0, 1) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ $resident->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $resident->type === 'owner' ? 'bg-[#0f766e]/10 text-[#0f766e]' : 'bg-[#d4a017]/10 text-[#d4a017]' }}">
                            {{ $resident->getTypeLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <a href="{{ route('units.show', $resident->unit) }}" class="text-[#0f766e] hover:underline">
                            {{ $resident->unit->number }}
                        </a>
                        <div class="text-xs text-gray-400">{{ $resident->unit->building->name }}</div>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600 ltr">{{ $resident->mobile ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $resident->resident_count }} نفر</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <button wire:click="openEdit({{ $resident->id }})" class="text-gray-400 hover:text-[#0f766e] text-xs">ویرایش</button>
                            <button wire:click="deactivate({{ $resident->id }})" wire:confirm="ساکن غیرفعال شود؟" class="text-gray-400 hover:text-red-500 text-xs">خروج</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-400">هیچ ساکنی یافت نشد</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $residents->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white">
                <h3 class="font-semibold text-gray-900">{{ $editingId ? 'ویرایش ساکن' : 'ساکن جدید' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">واحد <span class="text-red-500">*</span></label>
                    <select wire:model="unit_id"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="">انتخاب واحد</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}">{{ $u->building->name }} - {{ $u->number }}</option>
                        @endforeach
                    </select>
                    @error('unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع ساکن</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="type" type="radio" value="owner" class="text-[#0f766e]"/>
                            <span class="text-sm">مالک</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="type" type="radio" value="tenant" class="text-[#0f766e]"/>
                            <span class="text-sm">مستأجر</span>
                        </label>
                    </div>
                </div>

                <x-form-input wire:model="name" label="نام و نام خانوادگی" required/>
                <div class="grid grid-cols-2 gap-4">
                    <x-form-input wire:model="mobile" label="موبایل" type="tel" placeholder="09121234567"/>
                    <x-form-input wire:model="national_code" label="کد ملی"/>
                    <x-form-input wire:model="resident_count" label="تعداد نفر" type="number" min="1" required/>
                    <x-form-input wire:model="move_in_date" label="تاریخ ورود" type="date"/>
                    <x-form-input wire:model="move_out_date" label="تاریخ خروج" type="date"/>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'بروزرسانی' : 'ثبت ساکن' }}</span>
                        <span wire:loading>در حال ذخیره...</span>
                    </button>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
