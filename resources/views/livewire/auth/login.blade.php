<div class="flex min-h-screen flex-col justify-center gap-[26px] px-[30px]">
    {{-- Logo --}}
    <div class="flex flex-col items-center gap-4">
        <div class="flex h-[60px] w-[60px] items-center justify-center rounded-[18px] bg-[#5b5bd6] shadow-[0_8px_22px_-6px_rgba(91,91,214,.55)]">
            <div class="h-[22px] w-[22px] rounded-[5px] border-[3px] border-white"></div>
        </div>
        <div class="text-center">
            <div class="text-[22px] font-extrabold tracking-tight text-[#18181b]">ورود به سامانه</div>
            <div class="mt-[5px] text-[13.5px] text-[#71717a]">مدیریت ساختمان نگین</div>
        </div>
    </div>

    <form wire:submit="authenticate" class="flex flex-col gap-3.5">
        <label class="flex flex-col gap-[7px]">
            <span class="text-[13px] font-semibold text-[#3f3f46]">شمارهٔ موبایل</span>
            <input wire:model="mobile" type="tel" placeholder="09121234567" autocomplete="username"
                   dir="ltr"
                   class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-right text-[15px] text-[#18181b] outline-none focus:border-[#5b5bd6]">
            @error('mobile') <span class="text-xs text-[#dc2626]">{{ $message }}</span> @enderror
        </label>

        <label class="flex flex-col gap-[7px]">
            <span class="text-[13px] font-semibold text-[#3f3f46]">رمز عبور</span>
            <input wire:model="password" type="password" placeholder="••••••••" autocomplete="current-password"
                   class="h-12 rounded-xl border border-[#e4e4e7] bg-[#fafafa] px-3.5 text-[15px] text-[#18181b] outline-none focus:border-[#5b5bd6]">
            @error('password') <span class="text-xs text-[#dc2626]">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 self-start">
            <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#5b5bd6] focus:ring-[#5b5bd6]">
            <span class="text-[12.5px] text-[#71717a]">مرا به خاطر بسپار</span>
        </label>

        <x-submit-button target="authenticate" loadingLabel="در حال ورود…"
                class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15.5px] font-bold text-white shadow-[0_10px_22px_-8px_rgba(91,91,214,.6)] transition hover:bg-[#4f4fca]">ورود</x-submit-button>
    </form>

    <div class="text-center text-[12px] text-[#a1a1aa]">
        حساب کاربری ندارید؟
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-[#5b5bd6]">ثبت مجموعه جدید</a>
    </div>
</div>
