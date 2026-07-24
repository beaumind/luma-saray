<div class="space-y-6">

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="building_id"
            class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
            <option value="">همه ساختمان‌ها</option>
            @foreach($buildings as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
        @php $jNow = \Morilog\Jalali\Jalalian::now()->getYear(); @endphp
        <select wire:model.live="year"
            class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
            @for($y = $jNow; $y >= $jNow - 3; $y--)
                <option value="{{ $y }}">{{ \App\Support\JDate::toPersianDigits((string) $y) }}</option>
            @endfor
        </select>
        @php $jMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند']; @endphp
        <select wire:model.live="month"
            class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
            @foreach($jMonths as $i => $mName)
                <option value="{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}">{{ $mName }}</option>
            @endforeach
        </select>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-green-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">پرداخت این ماه</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($monthlyPayments) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">تومان</p>
        </div>
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">هزینه این ماه</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ number_format($monthlyExpenses) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">تومان</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">پرداخت امسال</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($yearlyPayments) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">تومان</p>
        </div>
        <div class="bg-white rounded-2xl border border-orange-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">هزینه امسال</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ number_format($yearlyExpenses) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">تومان</p>
        </div>
    </div>

    {{-- Monthly chart (simple HTML bars) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-5">روند ۱۲ ماه اخیر</h3>
        @php
            $maxVal = $monthlyData->max(fn($d) => max($d['payments'], $d['expenses'])) ?: 1;
        @endphp
        <div class="flex items-end gap-2 h-40">
            @foreach($monthlyData as $d)
            <div class="flex-1 flex flex-col items-center gap-1">
                <div class="w-full flex gap-0.5 items-end" style="height: 100px">
                    <div class="flex-1 rounded-t-sm bg-green-400 transition-all" style="height: {{ max(2, ($d['payments'] / $maxVal) * 100) }}%"></div>
                    <div class="flex-1 rounded-t-sm bg-red-400 transition-all" style="height: {{ max(2, ($d['expenses'] / $maxVal) * 100) }}%"></div>
                </div>
                <span class="text-gray-400 text-[10px] truncate w-full text-center">{{ mb_substr($d['label'], -2) }}</span>
            </div>
            @endforeach
        </div>
        <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-sm bg-green-400"></div>
                <span>پرداخت</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-sm bg-red-400"></div>
                <span>هزینه</span>
            </div>
        </div>
    </div>

    {{-- Debtor units --}}
    @if($debtorUnits->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">واحدهای بدهکار</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">واحد</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ساختمان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">بدهی (تومان)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($debtorUnits as $unit)
                <tr>
                    <td class="px-5 py-3">{{ $unit['number'] }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $unit['building']['name'] ?? '-' }}</td>
                    <td class="px-5 py-3 font-bold text-red-600">{{ number_format($unit['balance']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
