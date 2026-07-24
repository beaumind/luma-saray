<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <select wire:model.live="building_id"
            class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
            <option value="">همه ساختمان‌ها</option>
            @foreach($buildings as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
        <button wire:click="openCreate"
            class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            قالب شارژ جدید
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">عنوان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ساختمان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">نوع</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">دوره</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">مبلغ ثابت</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">سرانه</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($templates as $tpl)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5 font-medium text-gray-900">{{ $tpl->title }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $tpl->building->name }}</td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-full">{{ $tpl->getTypeLabel() }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600 text-xs">{{ $tpl->getPeriodLabel() }}</td>
                    <td class="px-5 py-3.5 text-gray-700">{{ $tpl->fixed_amount > 0 ? number_format($tpl->fixed_amount) . ' ت' : '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-700">{{ $tpl->per_resident_amount > 0 ? number_format($tpl->per_resident_amount) . ' ت' : '-' }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <button wire:click="openApply({{ $tpl->id }})"
                                class="text-xs bg-[#0f766e] text-white px-2.5 py-1 rounded-lg hover:bg-[#0f5f58] transition-colors">
                                اعمال
                            </button>
                            <button wire:click="openEdit({{ $tpl->id }})" class="text-gray-400 hover:text-[#0f766e] text-xs">ویرایش</button>
                            <button wire:click="delete({{ $tpl->id }})" wire:confirm="آیا از حذف مطمئن هستید؟" class="text-gray-400 hover:text-red-500 text-xs">حذف</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-gray-400">هیچ قالب شارژی تعریف نشده است</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $templates->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ $editingId ? 'ویرایش قالب شارژ' : 'قالب شارژ جدید' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ساختمان <span class="text-red-500">*</span></label>
                    <select wire:model="tpl_building_id"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="">انتخاب ساختمان</option>
                        @foreach($buildings as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('tpl_building_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <x-form-input wire:model="title" label="عنوان شارژ" placeholder="شارژ ماهانه" required/>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع شارژ</label>
                        <select wire:model.live="type"
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                            <option value="fixed">ثابت</option>
                            <option value="per_resident">به ازای هر نفر</option>
                            <option value="combined">ترکیبی</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">دوره</label>
                        <select wire:model="period"
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                            <option value="monthly">ماهانه</option>
                            <option value="quarterly">فصلی</option>
                            <option value="yearly">سالانه</option>
                        </select>
                    </div>
                </div>

                @if(in_array($type, ['fixed', 'combined']))
                    <x-form-input wire:model="fixed_amount" label="مبلغ ثابت (تومان)" type="number" min="0"/>
                @endif
                @if(in_array($type, ['per_resident', 'combined']))
                    <x-form-input wire:model="per_resident_amount" label="مبلغ سرانه هر نفر (تومان)" type="number" min="0"/>
                @endif

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'بروزرسانی' : 'ثبت' }}</span>
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

    {{-- Apply Modal --}}
    @if($showApplyModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm" wire:click.outside="$set('showApplyModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">اعمال شارژ</h3>
                <button wire:click="$set('showApplyModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="applyCharge" class="p-6 space-y-4">
                <x-form-input wire:model="apply_period" label="دوره (مثال: ۱۴۰۳/۰۵)" placeholder="1403/05" required/>
                <x-jalali-date-input wire:model="apply_date" label="تاریخ ثبت" required/>
                <p class="text-xs text-amber-600 bg-amber-50 rounded-xl p-3">
                    این شارژ برای تمام واحدهای فعال ساختمان ثبت خواهد شد.
                </p>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>اعمال شارژ</span>
                        <span wire:loading>در حال اعمال...</span>
                    </button>
                    <button type="button" wire:click="$set('showApplyModal', false)"
                        class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
