<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <div class="relative w-64">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="نام یا موبایل..."
                class="w-full pr-9 pl-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e]"/>
            <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <button wire:click="openCreate"
            class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            کاربر جدید
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">نام</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">موبایل</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">نقش</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">وضعیت</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-[#0f766e]/10 flex items-center justify-center text-[#0f766e] font-bold text-xs">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                                <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">شما</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600 ltr">{{ $user->mobile }}</td>
                    <td class="px-5 py-3.5">
                        @foreach($user->roles as $role)
                            @php
                                $roleNames = ['admin' => 'مدیر کل', 'manager' => 'مدیر', 'accountant' => 'حسابدار'];
                            @endphp
                            <span class="text-xs bg-[#0f766e]/10 text-[#0f766e] px-2.5 py-0.5 rounded-full">
                                {{ $roleNames[$role->name] ?? $role->name }}
                            </span>
                        @endforeach
                    </td>
                    <td class="px-5 py-3.5">
                        <button wire:click="toggleActive({{ $user->id }})"
                            class="text-xs px-2.5 py-0.5 rounded-full {{ $user->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}"
                            {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                            {{ $user->is_active ? 'فعال' : 'غیرفعال' }}
                        </button>
                    </td>
                    <td class="px-5 py-3.5">
                        <button wire:click="openEdit({{ $user->id }})" class="text-gray-400 hover:text-[#0f766e] text-xs">ویرایش</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-gray-400">هیچ کاربری یافت نشد</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $users->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ $editingId ? 'ویرایش کاربر' : 'کاربر جدید' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <x-form-input wire:model="name" label="نام کامل" required/>
                <x-form-input wire:model="mobile" label="شماره موبایل" type="tel" placeholder="09121234567" required/>
                <x-form-input wire:model="password" label="{{ $editingId ? 'رمز عبور جدید (اختیاری)' : 'رمز عبور' }}" type="password" :required="!$editingId"/>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">نقش <span class="text-red-500">*</span></label>
                    <select wire:model="role"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        @foreach($roles as $r)
                            @php $rNames = ['admin' => 'مدیر کل', 'manager' => 'مدیر', 'accountant' => 'حسابدار']; @endphp
                            <option value="{{ $r->name }}">{{ $rNames[$r->name] ?? $r->name }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#0f766e]"/>
                    <span class="text-sm text-gray-700">کاربر فعال باشد</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <x-submit-button target="save" loadingLabel="در حال ذخیره..."
                        class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors">{{ $editingId ? 'بروزرسانی' : 'ثبت کاربر' }}</x-submit-button>
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
