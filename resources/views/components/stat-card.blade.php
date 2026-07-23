@props(['title', 'value', 'icon', 'color' => 'primary'])

@php
$icons = [
    'building' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    'grid' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
    'banknote' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'receipt' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
];
$colors = [
    'primary' => ['bg' => 'bg-[#0f766e]/10', 'icon' => 'text-[#0f766e]', 'border' => 'border-[#0f766e]/20'],
    'gold' => ['bg' => 'bg-[#d4a017]/10', 'icon' => 'text-[#d4a017]', 'border' => 'border-[#d4a017]/20'],
    'green' => ['bg' => 'bg-green-50', 'icon' => 'text-green-600', 'border' => 'border-green-100'],
    'red' => ['bg' => 'bg-red-50', 'icon' => 'text-red-500', 'border' => 'border-red-100'],
];
$c = $colors[$color] ?? $colors['primary'];
$iconPath = $icons[$icon] ?? $icons['grid'];
@endphp

<div class="bg-white rounded-2xl border {{ $c['border'] }} p-5 shadow-sm">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm text-gray-500 font-medium">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $value }}</p>
        </div>
        <div class="w-11 h-11 rounded-xl {{ $c['bg'] }} flex items-center justify-center">
            <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                {!! $iconPath !!}
            </svg>
        </div>
    </div>
</div>
