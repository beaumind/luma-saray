<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-gray-200">
        <button wire:click="$set('tab', 'categories')"
            class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'categories' ? 'border-[#0f766e] text-[#0f766e]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            دسته‌بندی هزینه
        </button>
        <button wire:click="$set('tab', 'profile')"
            class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'profile' ? 'border-[#0f766e] text-[#0f766e]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            پروفایل من
        </button>
    </div>

    @if($tab === 'categories')
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="font-semibold text-gray-900">دسته‌بندی‌های هزینه</h3>
            <button wire:click="openCategoryCreate"
                class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                دسته‌بندی جدید
            </button>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-right font-semibold text-gray-600">نام</th>
                        <th class="px-5 py-3 text-right font-semibold text-gray-600">رنگ</th>
                        <th class="px-5 py-3 text-right font-semibold text-gray-600">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($categories as $cat)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3.5 font-medium text-gray-900">{{ $cat->name }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full" style="background-color: {{ $cat->color }}"></div>
                                <span class="text-xs text-gray-400 ltr">{{ $cat->color }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <button wire:click="openCategoryEdit({{ $cat->id }})" class="text-gray-400 hover:text-[#0f766e] text-xs">ویرایش</button>
                                <button wire:click="deleteCategory({{ $cat->id }})" wire:confirm="حذف شود؟" class="text-gray-400 hover:text-red-500 text-xs">حذف</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center text-gray-400">هیچ دسته‌بندی تعریف نشده است</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($tab === 'profile')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-md">
        <div class="flex items-center gap-4 mb-5">
            <div class="w-14 h-14 rounded-full bg-[#0f766e]/10 flex items-center justify-center text-[#0f766e] font-bold text-xl">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                <p class="text-gray-400 text-sm">{{ auth()->user()->mobile }}</p>
            </div>
        </div>
        <p class="text-sm text-gray-500">برای تغییر اطلاعات پروفایل با مدیر سیستم تماس بگیرید.</p>
    </div>
    @endif

    {{-- Category Modal --}}
    @if($showCategoryModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm" wire:click.outside="$set('showCategoryModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ $editingCategoryId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h3>
                <button wire:click="$set('showCategoryModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="saveCategory" class="p-6 space-y-4">
                <x-form-input wire:model="cat_name" label="نام دسته‌بندی" required/>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رنگ</label>
                    <input wire:model="cat_color" type="color"
                        class="w-full h-10 border border-gray-200 rounded-xl cursor-pointer"/>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors">
                        ذخیره
                    </button>
                    <button type="button" wire:click="$set('showCategoryModal', false)"
                        class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
