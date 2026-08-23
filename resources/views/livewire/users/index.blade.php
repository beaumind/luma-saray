@php
    use App\Support\Fmt;
    $roleNames = ['admin' => 'مدیر کل', 'manager' => 'مدیر', 'accountant' => 'حسابدار'];
    $roleStyle = ['admin' => ['#eef0fb', '#5b5bd6'], 'manager' => ['#e9f7ef', '#16a34a'], 'accountant' => ['#fdf3e7', '#d97706']];
@endphp
<div>
    <x-app-header title="کاربران" :back="route('dashboard')" :subtitle="Fmt::fa($users->total()).' کاربر'">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>کاربر جدید
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="جستجوی نام یا موبایل…"
               class="mb-3 w-full rounded-[12px] border border-[#e4e4e7] bg-white px-4 py-3 text-[13px] outline-none focus:border-[#5b5bd6]">

        <div class="flex flex-col gap-[9px]">
            @forelse($users as $user)
                @php
                    $role = $user->roles->first()?->name;
                    [$badgeBg, $badgeColor] = $roleStyle[$role] ?? ['#f4f4f5', '#71717a'];
                @endphp
                <button wire:click="openEdit({{ $user->id }})" type="button"
                        class="flex w-full items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px] text-right">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] bg-[#eef0fb] text-[14px] font-extrabold text-[#5b5bd6]">{{ mb_substr($user->name, 0, 1) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="truncate text-[13.5px] font-bold text-[#18181b]">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                                <span class="flex-none rounded-full bg-[#eef0fb] px-2 py-[1px] text-[10px] font-bold text-[#5b5bd6]">شما</span>
                            @endif
                        </div>
                        <div class="text-[11.5px] text-[#a1a1aa]" dir="ltr">{{ Fmt::fa($user->mobile) }}</div>
                    </div>
                    <div class="flex flex-none flex-col items-end gap-1.5">
                        @if($role)
                            <span class="rounded-full px-2.5 py-[3px] text-[10.5px] font-bold" style="background:{{ $badgeBg }};color:{{ $badgeColor }}">{{ $roleNames[$role] ?? $role }}</span>
                        @endif
                        <span class="rounded-full px-2 py-[2px] text-[10px] font-bold" style="background:{{ $user->is_active ? '#e9f7ef' : '#f4f4f5' }};color:{{ $user->is_active ? '#16a34a' : '#a1a1aa' }}">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</span>
                    </div>
                </button>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">کاربری یافت نشد</div>
            @endforelse
        </div>
        @if($users->hasPages())<div class="mt-4">{{ $users->links() }}</div>@endif
    </div>

    <x-sheet model="showModal" :title="$editingId ? 'ویرایش کاربر' : 'کاربر جدید'">
        <form wire:submit="save" class="flex flex-col gap-3">
            <x-input wire:model="name" label="نام و نام خانوادگی" />
            <x-input wire:model="mobile" label="شماره موبایل" type="tel" />
            <x-input wire:model="password" type="password" :label="$editingId ? 'رمز عبور جدید (اختیاری)' : 'رمز عبور'" />

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">نقش</span>
                <select wire:model="role" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    @foreach($roles as $r)<option value="{{ $r->name }}">{{ $roleNames[$r->name] ?? $r->name }}</option>@endforeach
                </select>
                @error('role')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>

            <label class="flex cursor-pointer items-center justify-between rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] py-3">
                <span class="text-[13px] font-semibold text-[#3f3f46]">کاربر فعال باشد</span>
                <input wire:model="is_active" type="checkbox" class="h-5 w-5 rounded accent-[#5b5bd6]">
            </label>

            <x-submit-button target="save" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">{{ $editingId ? 'بروزرسانی کاربر' : 'ثبت کاربر' }}</x-submit-button>
        </form>
    </x-sheet>
</div>
