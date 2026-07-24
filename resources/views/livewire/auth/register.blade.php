<div class="flex min-h-screen flex-col justify-center gap-[22px] px-[30px] py-10">
    <div class="flex flex-col items-center gap-4">
        <div class="flex h-[60px] w-[60px] items-center justify-center rounded-[18px] bg-[#5b5bd6] shadow-[0_8px_22px_-6px_rgba(91,91,214,.55)]">
            <div class="h-[22px] w-[22px] rounded-[5px] border-[3px] border-white"></div>
        </div>
        <div class="text-center">
            <div class="text-[22px] font-extrabold tracking-tight text-[#18181b]">ایجاد حساب کاربری</div>
            <div class="mt-[5px] text-[13.5px] text-[#71717a]">مجموعهٔ خود را ثبت کنید</div>
        </div>
    </div>

    <form wire:submit="register" class="flex flex-col gap-3">
        <label class="flex flex-col gap-1.5">
            <span class="text-[13px] font-semibold text-[#3f3f46]">نام مجموعه / شرکت</span>
            <input wire:model="organization_name" type="text" placeholder="مثلاً: مدیریت ساختمان نگین"
                   class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-[15px] outline-none focus:border-[#5b5bd6]">
            @error('organization_name')<span class="text-xs text-[#dc2626]">{{ $message }}</span>@enderror
        </label>
        <label class="flex flex-col gap-1.5">
            <span class="text-[13px] font-semibold text-[#3f3f46]">نام و نام خانوادگی</span>
            <input wire:model="name" type="text" placeholder="نام مدیر"
                   class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-[15px] outline-none focus:border-[#5b5bd6]">
            @error('name')<span class="text-xs text-[#dc2626]">{{ $message }}</span>@enderror
        </label>
        <label class="flex flex-col gap-1.5">
            <span class="text-[13px] font-semibold text-[#3f3f46]">شمارهٔ موبایل</span>
            <input wire:model="mobile" type="tel" placeholder="09121234567" dir="ltr" autocomplete="username"
                   class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-right text-[15px] outline-none focus:border-[#5b5bd6]">
            @error('mobile')<span class="text-xs text-[#dc2626]">{{ $message }}</span>@enderror
        </label>
        <div class="flex gap-2.5">
            <label class="flex flex-1 flex-col gap-1.5">
                <span class="text-[13px] font-semibold text-[#3f3f46]">رمز عبور</span>
                <input wire:model="password" type="password" placeholder="••••••••" autocomplete="new-password"
                       class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-[15px] outline-none focus:border-[#5b5bd6]">
            </label>
            <label class="flex flex-1 flex-col gap-1.5">
                <span class="text-[13px] font-semibold text-[#3f3f46]">تکرار رمز</span>
                <input wire:model="password_confirmation" type="password" placeholder="••••••••" autocomplete="new-password"
                       class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-[15px] outline-none focus:border-[#5b5bd6]">
            </label>
        </div>
        @error('password')<span class="-mt-1 text-xs text-[#dc2626]">{{ $message }}</span>@enderror

        <button type="submit" wire:loading.attr="disabled"
                class="mt-1 flex h-[50px] items-center justify-center rounded-[13px] bg-[#5b5bd6] text-[15.5px] font-bold text-white shadow-[0_10px_22px_-8px_rgba(91,91,214,.6)] disabled:opacity-70">
            <span wire:loading.remove wire:target="register">ایجاد حساب</span>
            <span wire:loading wire:target="register">در حال ایجاد…</span>
        </button>
    </form>

    <div class="text-center text-[13px] text-[#71717a]">
        حساب کاربری دارید؟
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-[#5b5bd6]">ورود</a>
    </div>
</div>
