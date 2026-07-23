<div class="space-y-6">

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card
            title="کل ساختمان‌ها"
            :value="$buildings->count()"
            icon="building"
            color="primary"
        />
        <x-stat-card
            title="کل واحدها"
            :value="$totalUnits"
            icon="grid"
            color="gold"
        />
        <x-stat-card
            title="پرداخت‌های این ماه"
            :value="number_format($thisMonthPayments) . ' تومان'"
            icon="banknote"
            color="green"
        />
        <x-stat-card
            title="هزینه‌های این ماه"
            :value="number_format($thisMonthExpenses) . ' تومان'"
            icon="receipt"
            color="red"
        />
    </div>

    {{-- Balance overview --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">وضعیت مالی کلی</h3>
            <span class="text-xs text-gray-400">مجموع بدهی‌های معوق</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-3xl font-bold {{ $totalBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format(abs($totalBalance)) }}
                <span class="text-base font-normal text-gray-500">تومان</span>
            </div>
            @if($totalBalance > 0)
                <span class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-sm font-medium">بدهکار</span>
            @else
                <span class="px-3 py-1 bg-green-50 text-green-600 rounded-full text-sm font-medium">بستانکار</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {{-- Recent Transactions --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">تراکنش‌های اخیر</h3>
                <a href="{{ route('payments.index') }}" class="text-sm text-[#0f766e] hover:underline">مشاهده همه</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentTransactions as $tx)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $tx->direction === 'credit' ? 'bg-green-50' : 'bg-red-50' }}">
                        @if($tx->direction === 'credit')
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $tx->description }}</p>
                        <p class="text-xs text-gray-400">{{ $tx->unit->building->name ?? '' }} - {{ $tx->unit->number ?? '' }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold {{ $tx->direction === 'credit' ? 'text-green-600' : 'text-red-500' }}">
                            {{ $tx->direction === 'credit' ? '+' : '-' }}{{ number_format($tx->amount) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($tx->transaction_date))->format('Y/m/d') }}</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-gray-400 text-sm">تراکنشی ثبت نشده است</div>
                @endforelse
            </div>
        </div>

        {{-- Debtor Units --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">واحدهای بدهکار</h3>
                <a href="{{ route('units.index') }}" class="text-sm text-[#0f766e] hover:underline">مشاهده همه</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($debtorUnits as $unit)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-red-600 font-bold text-sm flex-shrink-0">
                        {{ mb_substr($unit->number, -2) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $unit->number }}</p>
                        <p class="text-xs text-gray-400">{{ $unit->building->name }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-bold text-red-600">{{ number_format($unit->balance) }}</p>
                        <p class="text-xs text-gray-400">تومان</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-gray-400 text-sm">همه واحدها تسویه هستند 🎉</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Buildings overview --}}
    @if($buildings->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">ساختمان‌ها</h3>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($buildings as $building)
            <a href="{{ route('buildings.show', $building) }}" class="block border border-gray-100 rounded-xl p-4 hover:border-[#0f766e] hover:shadow-sm transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0f766e]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#0f766e]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <span class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full">فعال</span>
                </div>
                <h4 class="font-semibold text-gray-900">{{ $building->name }}</h4>
                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $building->address }}</p>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                    <span>{{ $building->units_count }} واحد</span>
                    <span>{{ $building->floors }} طبقه</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
