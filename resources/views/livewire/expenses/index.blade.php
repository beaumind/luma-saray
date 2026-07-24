<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="جستجو..."
                    class="pr-9 pl-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] w-48"/>
                <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select wire:model.live="building_id_filter"
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
            هزینه جدید
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">تاریخ</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">عنوان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ساختمان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">دسته‌بندی</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">مبلغ</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">توزیع</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5 text-gray-500 text-xs">
                        <x-jdate :value="$expense->expense_date" />
                    </td>
                    <td class="px-5 py-3.5 font-medium text-gray-900">{{ $expense->title }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $expense->building->name }}</td>
                    <td class="px-5 py-3.5">
                        @if($expense->category)
                            <span class="text-xs px-2 py-0.5 rounded-full text-white" style="background-color: {{ $expense->category->color }}">
                                {{ $expense->category->name }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-gray-900">{{ number_format($expense->amount) }} ت</td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs {{ $expense->distribution === 'all_units' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-700' }} px-2.5 py-0.5 rounded-full">
                            {{ $expense->distribution === 'all_units' ? 'همه واحدها' : 'انتخابی' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-400">هیچ هزینه‌ای ثبت نشده است</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $expenses->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white">
                <h3 class="font-semibold text-gray-900">ثبت هزینه جدید</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ساختمان <span class="text-red-500">*</span></label>
                    <select wire:model.live="building_id"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="">انتخاب ساختمان</option>
                        @foreach($buildings as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('building_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">دسته‌بندی</label>
                    <select wire:model="expense_category_id"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="">بدون دسته‌بندی</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <x-form-input wire:model="title" label="عنوان هزینه" required/>
                <div class="grid grid-cols-2 gap-4">
                    <x-form-input wire:model="amount" label="مبلغ (تومان)" type="number" min="1" required/>
                    <x-jalali-date-input wire:model="expense_date" label="تاریخ" required/>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">توزیع هزینه</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="distribution" type="radio" value="all_units" class="text-[#0f766e]"/>
                            <span class="text-sm">همه واحدها</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="distribution" type="radio" value="selected_units" class="text-[#0f766e]"/>
                            <span class="text-sm">واحدهای انتخابی</span>
                        </label>
                    </div>
                </div>

                @if($distribution === 'selected_units' && $units->count() > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">انتخاب واحدها</label>
                    <div class="grid grid-cols-3 gap-2 max-h-32 overflow-y-auto border border-gray-200 rounded-xl p-3">
                        @foreach($units as $u)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" wire:model="selected_unit_ids" value="{{ $u->id }}" class="text-[#0f766e]"/>
                            <span class="text-xs">{{ $u->number }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">مسئول پرداخت</label>
                    <select wire:model="responsible"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="owner">مالک</option>
                        <option value="tenant">مستأجر</option>
                        <option value="both">هر دو</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">توضیحات</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">پیوست‌ها (تصویر یا PDF)</label>
                    <input wire:model="attachments" type="file" multiple accept="image/*,.pdf"
                        class="w-full text-sm text-gray-500 file:ml-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0f766e]/10 file:text-[#0f766e] hover:file:bg-[#0f766e]/20"/>
                    @error('attachments.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>ثبت و توزیع هزینه</span>
                        <span wire:loading>در حال ثبت...</span>
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
