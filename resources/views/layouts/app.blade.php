<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'نگین' }} — مدیریت ساختمان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-[#e7e7ea] text-[#18181b]" style="background-image:radial-gradient(circle at 1px 1px,#d7d7dc 1px,transparent 0);background-size:22px 22px;font-family:'Vazirmatn',system-ui,sans-serif">

<div
    x-data="{ moreOpen: false }"
    class="relative mx-auto flex min-h-screen w-full max-w-[440px] flex-col bg-[#f7f7f8] shadow-[0_20px_60px_-20px_rgba(20,20,30,.25)]"
>
    {{-- Page content (each page includes its own <x-app-header>) --}}
    <div class="flex-1 pb-24">
        {{ $slot }}
    </div>

    {{-- ============ BOTTOM NAV ============ --}}
    @php
        $navItems = [
            ['label' => 'داشبورد', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
            ['label' => 'واحدها', 'route' => 'units.index', 'active' => request()->routeIs('units.*')],
            ['label' => 'شارژ', 'route' => 'charges.index', 'active' => request()->routeIs('charges.*')],
            ['label' => 'پرداخت', 'route' => 'payments.index', 'active' => request()->routeIs('payments.*')],
        ];
        $moreActive = request()->routeIs('residents.*') || request()->routeIs('expenses.*') || request()->routeIs('buildings.*') || request()->routeIs('reports.*') || request()->routeIs('users.*') || request()->routeIs('settings.*');
    @endphp
    <div class="fixed bottom-0 left-1/2 z-40 flex h-[78px] w-full max-w-[440px] -translate-x-1/2 border-t border-[#ececef] bg-white/95 px-2 pb-4 pt-2 backdrop-blur-md">
        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex flex-1 flex-col items-center gap-1 pt-1">
                <span class="h-[22px] w-[22px] rounded-[7px] border-2 {{ $item['active'] ? 'border-[#5b5bd6] bg-[#5b5bd6]' : 'border-[#a1a1aa] bg-transparent' }}"></span>
                <span class="text-[10.5px] font-semibold {{ $item['active'] ? 'text-[#5b5bd6]' : 'text-[#a1a1aa]' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
        <button type="button" @click="moreOpen = true"
                class="flex flex-1 flex-col items-center gap-1 pt-1">
            <span class="h-[22px] w-[22px] rounded-[7px] border-2 {{ $moreActive ? 'border-[#5b5bd6] bg-[#5b5bd6]' : 'border-[#a1a1aa] bg-transparent' }}"></span>
            <span class="text-[10.5px] font-semibold {{ $moreActive ? 'text-[#5b5bd6]' : 'text-[#a1a1aa]' }}">بیشتر</span>
        </button>
    </div>

    {{-- ============ MORE SHEET ============ --}}
    <div x-show="moreOpen" x-cloak class="fixed inset-0 z-[70]" style="display:none">
        <div x-show="moreOpen" x-transition.opacity @click="moreOpen = false"
             class="absolute inset-0 bg-[#0a0a0f]/40"></div>
        <div x-show="moreOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             class="absolute bottom-0 left-1/2 w-full max-w-[440px] -translate-x-1/2 rounded-t-3xl bg-white px-4 pb-7 pt-2.5">
            <div class="mx-auto mb-3.5 mt-1.5 h-1 w-9 rounded-full bg-[#e4e4e7]"></div>
            <div class="mb-3 px-1 text-[15px] font-extrabold text-[#18181b]">بیشتر</div>
            <div class="grid grid-cols-3 gap-2.5">
                @php
                    $moreLinks = [
                        ['icon' => '👥', 'label' => 'ساکنان', 'route' => 'residents.index'],
                        ['icon' => '🧾', 'label' => 'هزینه‌ها', 'route' => 'expenses.index'],
                        ['icon' => '🏢', 'label' => 'ساختمان‌ها', 'route' => 'buildings.index'],
                        ['icon' => '📊', 'label' => 'گزارش‌ها', 'route' => 'reports.index'],
                        ['icon' => '👤', 'label' => 'کاربران', 'route' => 'users.index'],
                        ['icon' => '⚙️', 'label' => 'تنظیمات', 'route' => 'settings.index'],
                    ];
                @endphp
                @foreach($moreLinks as $link)
                    <a href="{{ route($link['route']) }}" wire:navigate @click="moreOpen = false"
                       class="flex flex-col items-center gap-1.5 rounded-2xl border border-[#ececef] bg-[#f7f7f8] px-2 py-4">
                        <span class="text-xl">{{ $link['icon'] }}</span>
                        <span class="text-[11.5px] font-semibold text-[#3f3f46]">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============ TOAST ============ --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2800)"
             x-transition
             class="fixed bottom-[92px] left-1/2 z-[90] flex w-[calc(100%-40px)] max-w-[400px] -translate-x-1/2 items-center gap-3 rounded-2xl bg-[#18181b] px-4 py-3 text-white shadow-xl">
            <span class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-[#16a34a] text-[13px] text-white">✓</span>
            <span class="flex-1 text-[13.5px] font-semibold">{{ session('success') }}</span>
        </div>
    @endif
</div>

@livewireScripts
</body>
</html>
