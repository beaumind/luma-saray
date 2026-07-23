<div class="space-y-5">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('buildings.index') }}" class="hover:text-[#0f766e]">ساختمان‌ها</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">{{ $building->name }}</span>
    </div>

    {{-- Building header --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-[#0f766e]/10 flex items-center justify-center">
                    <svg class="w-7 h-7 text-[#0f766e]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $building->name }}</h2>
                    <p class="text-gray-500 text-sm mt-0.5">{{ $building->address }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <span>{{ $building->total_units }} واحد</span>
                        <span>{{ $building->floors }} طبقه</span>
                        <span>{{ $building->city }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('units.index', ['building_id' => $building->id]) }}"
                class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                واحد جدید
            </a>
        </div>
    </div>

    {{-- Units grid by floor --}}
    @php $byFloor = $building->units->groupBy('floor') @endphp

    @foreach($byFloor->sortKeys() as $floor => $units)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
            <h3 class="font-medium text-gray-700 text-sm">طبقه {{ $floor }}</h3>
        </div>
        <div class="p-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach($units as $unit)
            @php
                $balance = $unit->balance;
                $owner = $unit->activeResidents->firstWhere('type', 'owner');
                $tenant = $unit->activeResidents->firstWhere('type', 'tenant');
            @endphp
            <a href="{{ route('units.show', $unit) }}"
                class="border rounded-xl p-3 hover:shadow-sm transition-all
                    {{ $balance > 0 ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-white hover:border-[#0f766e]/30' }}">
                <div class="flex items-start justify-between mb-2">
                    <span class="font-bold text-gray-900 text-sm">{{ $unit->number }}</span>
                    @if($balance > 0)
                        <span class="text-xs text-red-600 font-medium">بدهکار</span>
                    @else
                        <span class="text-xs text-green-600 font-medium">تسویه</span>
                    @endif
                </div>
                @if($owner)
                    <p class="text-xs text-gray-600 truncate">{{ $owner->name }}</p>
                @endif
                @if($tenant)
                    <p class="text-xs text-gray-400 truncate">مستأجر: {{ $tenant->name }}</p>
                @endif
                @if($balance > 0)
                    <p class="text-xs font-semibold text-red-600 mt-1.5">{{ number_format($balance) }} ت</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endforeach

</div>
