<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لوما سرای' }} - مدیریت هوشمند ساختمان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 font-sans">

<div class="flex h-full" dir="rtl" x-data="{ sidebarOpen: false }">

    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        style="display:none"
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 right-0 z-50 w-64 bg-[#111827] flex flex-col shadow-xl transition-transform duration-300"
        :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
    >
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
            <div class="w-10 h-10 rounded-xl bg-[#0f766e] flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-[#d4a017]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9,22 9,12 15,12 15,22"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-white font-bold text-lg leading-tight">
                    <span class="text-white">Luma</span><span class="text-[#d4a017]">Saray</span>
                </div>
                <div class="text-gray-400 text-xs">مدیریت هوشمند ساختمان</div>
            </div>
            {{-- Close button (mobile only) --}}
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white p-1 -ml-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-1">
            <x-nav-item href="{{ route('dashboard') }}" icon="home" :active="request()->routeIs('dashboard')" @click="sidebarOpen = false">
                داشبورد
            </x-nav-item>
            <x-nav-item href="{{ route('buildings.index') }}" icon="building" :active="request()->routeIs('buildings.*')" @click="sidebarOpen = false">
                ساختمان‌ها
            </x-nav-item>
            <x-nav-item href="{{ route('units.index') }}" icon="grid" :active="request()->routeIs('units.*')" @click="sidebarOpen = false">
                واحدها
            </x-nav-item>
            <x-nav-item href="{{ route('residents.index') }}" icon="users" :active="request()->routeIs('residents.*')" @click="sidebarOpen = false">
                ساکنین
            </x-nav-item>

            <div class="pt-3 pb-1">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">مالی</p>
            </div>

            <x-nav-item href="{{ route('charges.index') }}" icon="credit-card" :active="request()->routeIs('charges.*')" @click="sidebarOpen = false">
                شارژها
            </x-nav-item>
            <x-nav-item href="{{ route('expenses.index') }}" icon="receipt" :active="request()->routeIs('expenses.*')" @click="sidebarOpen = false">
                هزینه‌ها
            </x-nav-item>
            <x-nav-item href="{{ route('payments.index') }}" icon="banknote" :active="request()->routeIs('payments.*')" @click="sidebarOpen = false">
                پرداخت‌ها
            </x-nav-item>
            <x-nav-item href="{{ route('reports.index') }}" icon="bar-chart" :active="request()->routeIs('reports.*')" @click="sidebarOpen = false">
                گزارش‌ها
            </x-nav-item>

            <div class="pt-3 pb-1">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">مدیریت</p>
            </div>

            <x-nav-item href="{{ route('users.index') }}" icon="user-cog" :active="request()->routeIs('users.*')" @click="sidebarOpen = false">
                کاربران
            </x-nav-item>
            <x-nav-item href="{{ route('settings.index') }}" icon="settings" :active="request()->routeIs('settings.*')" @click="sidebarOpen = false">
                تنظیمات
            </x-nav-item>
        </nav>

        {{-- User section --}}
        <div class="border-t border-white/10 px-4 py-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#0f766e] flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-gray-400 text-xs">{{ auth()->user()->mobile }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-white transition-colors p-1" title="خروج">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 lg:mr-64 flex flex-col min-h-screen">
        {{-- Top bar --}}
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 px-4 py-4 flex items-center gap-3">
            {{-- Hamburger (mobile only) --}}
            <button
                @click="sidebarOpen = true"
                class="lg:hidden text-gray-500 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-100"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title ?? 'داشبورد' }}</h1>
                @isset($breadcrumb)
                    <div class="text-sm text-gray-500 mt-0.5">{{ $breadcrumb }}</div>
                @endisset
            </div>

            <span class="text-sm text-gray-500 hidden sm:block">{{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d') }}</span>
        </header>

        {{-- Page content --}}
        <div class="flex-1 p-4 lg:p-6">
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
