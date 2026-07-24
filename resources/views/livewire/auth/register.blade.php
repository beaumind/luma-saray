<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#0f766e] mb-4 shadow-lg">
                <svg class="w-9 h-9 text-[#d4a017]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">
                <span>Luma</span><span class="text-[#d4a017]">Saray</span>
            </h1>
            <p class="text-gray-400 text-sm mt-1">مدیریت هوشمند ساختمان</p>
        </div>

        {{-- Register card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-2 text-center">ایجاد حساب کاربری</h2>
            <p class="text-sm text-gray-500 mb-6 text-center">مجموعه خود را ثبت کنید و مدیریت را آغاز کنید</p>

            <form wire:submit="register" class="space-y-5">
                {{-- Organization name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">نام مجموعه / شرکت</label>
                    <input
                        wire:model="organization_name"
                        type="text"
                        placeholder="مثلاً: مدیریت ساختمان مهر"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all"
                    />
                    @error('organization_name')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Admin name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">نام و نام خانوادگی شما</label>
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="نام مدیر"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all"
                    />
                    @error('name')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mobile --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">شماره موبایل</label>
                    <input
                        wire:model="mobile"
                        type="tel"
                        placeholder="09121234567"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all ltr placeholder:text-right"
                        dir="ltr"
                        autocomplete="username"
                    />
                    @error('mobile')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رمز عبور</label>
                    <input
                        wire:model="password"
                        type="password"
                        placeholder="••••••••"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all"
                        autocomplete="new-password"
                    />
                    @error('password')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password confirmation --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">تکرار رمز عبور</label>
                    <input
                        wire:model="password_confirmation"
                        type="password"
                        placeholder="••••••••"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:border-transparent transition-all"
                        autocomplete="new-password"
                    />
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full bg-[#0f766e] hover:bg-[#0f5f58] text-white font-semibold py-3 rounded-xl transition-colors duration-200 flex items-center justify-center gap-2"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75"
                >
                    <span wire:loading.remove>ایجاد حساب</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        در حال ایجاد...
                    </span>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-600">
                    حساب کاربری دارید؟
                    <a href="{{ route('login') }}" wire:navigate class="text-[#0f766e] font-semibold hover:underline">ورود</a>
                </p>
            </div>
        </div>
    </div>
</div>
