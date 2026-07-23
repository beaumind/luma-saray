<div class="space-y-5">

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="relative w-72">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="جستجو در ساختمان‌ها..."
                class="w-full pr-10 pl-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent"/>
            <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <button wire:click="openCreate"
            class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            ساختمان جدید
        </button>
    </div>

    {{-- Grid --}}
    @if($buildings->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-400">هیچ ساختمانی یافت نشد</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($buildings as $building)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                <div class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-[#0f766e]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#0f766e]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="openEdit({{ $building->id }})" class="text-gray-400 hover:text-[#0f766e] p-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $building->id }})" wire:confirm="آیا از حذف این ساختمان مطمئن هستید؟" class="text-gray-400 hover:text-red-500 p-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="font-semibold text-gray-900 text-base">{{ $building->name }}</h3>
                    <p class="text-sm text-gray-400 mt-1 truncate">{{ $building->address }}</p>

                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>{{ $building->units_count }} واحد</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 8h18M3 12h18"/>
                            </svg>
                            <span>{{ $building->floors }} طبقه</span>
                        </div>
                    </div>

                    @if($building->manager_name)
                    <div class="mt-3 pt-3 border-t border-gray-50 text-xs text-gray-400">
                        مدیر: {{ $building->manager_name }}
                        @if($building->manager_mobile)
                            | {{ $building->manager_mobile }}
                        @endif
                    </div>
                    @endif
                </div>

                <div class="px-5 py-3 bg-gray-50 flex gap-2">
                    <a href="{{ route('buildings.show', $building) }}"
                        class="flex-1 text-center text-sm text-[#0f766e] font-medium hover:underline">
                        مشاهده واحدها
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div>{{ $buildings->links() }}</div>
    @endif

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ $editingId ? 'ویرایش ساختمان' : 'ساختمان جدید' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <x-form-input wire:model="name" label="نام ساختمان" placeholder="برج سپهر" required/>
                    </div>
                    <div class="col-span-2">
                        <x-form-input wire:model="address" label="آدرس" placeholder="تهران، ولیعصر..." required/>
                    </div>
                    <div>
                        <x-form-input wire:model="city" label="شهر" placeholder="تهران" required/>
                    </div>
                    <div>
                        <x-form-input wire:model="floors" label="تعداد طبقات" type="number" min="1" required/>
                    </div>
                    <div>
                        <x-form-input wire:model="total_units" label="تعداد واحدها" type="number" min="1" required/>
                    </div>
                    <div>
                        <x-form-input wire:model="manager_name" label="نام مدیر"/>
                    </div>
                    <div class="col-span-2">
                        <x-form-input wire:model="manager_mobile" label="موبایل مدیر" type="tel" placeholder="09121234567"/>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove>{{ $editingId ? 'بروزرسانی' : 'ثبت ساختمان' }}</span>
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
