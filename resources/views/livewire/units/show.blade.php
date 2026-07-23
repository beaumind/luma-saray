<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('units.index') }}" class="hover:text-[#0f766e]">واحدها</a>
        <span>/</span>
        <a href="{{ route('buildings.show', $unit->building) }}" class="hover:text-[#0f766e]">{{ $unit->building->name }}</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">{{ $unit->number }}</span>
    </div>

    {{-- Unit header --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $unit->number }}</h2>
                    <p class="text-gray-500 text-sm">{{ $unit->building->name }} | طبقه {{ $unit->floor }}</p>
                    <div class="flex flex-wrap gap-3 mt-3">
                        @if($unit->area)
                            <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">{{ $unit->area }} متر</span>
                        @endif
                        <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">{{ $unit->bedrooms }} اتاق</span>
                        @if($unit->parking_count)
                            <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">{{ $unit->parking_count }} پارکینگ</span>
                        @endif
                    </div>
                </div>
                <button wire:click="openPayment"
                    class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    ثبت پرداخت
                </button>
            </div>

            {{-- Residents --}}
            <div class="mt-4 pt-4 border-t border-gray-50 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($unit->activeResidents as $resident)
                <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-3">
                    <div class="w-9 h-9 rounded-full bg-[#0f766e]/10 flex items-center justify-center text-[#0f766e] font-bold text-sm">
                        {{ mb_substr($resident->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 text-sm">{{ $resident->name }}</p>
                        <p class="text-xs text-gray-400">{{ $resident->getTypeLabel() }} | {{ $resident->resident_count }} نفر</p>
                        @if($resident->mobile)
                            <p class="text-xs text-gray-400">{{ $resident->mobile }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Balance card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col items-center justify-center text-center">
            <p class="text-sm text-gray-500 mb-2">وضعیت مالی</p>
            <p class="text-3xl font-bold {{ $balance > 0 ? 'text-red-600' : ($balance < 0 ? 'text-blue-600' : 'text-green-600') }}">
                {{ number_format(abs($balance)) }}
            </p>
            <p class="text-gray-400 text-sm mt-1">تومان</p>
            @if($balance > 0)
                <span class="mt-3 px-4 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">بدهکار</span>
            @elseif($balance < 0)
                <span class="mt-3 px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-sm font-medium">اعتبار</span>
            @else
                <span class="mt-3 px-4 py-1.5 bg-green-50 text-green-600 rounded-full text-sm font-medium">تسویه حساب</span>
            @endif
        </div>
    </div>

    {{-- Transactions --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">تاریخچه تراکنش‌ها</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">تاریخ</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">نوع</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">توضیح</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">مبلغ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transactions as $tx)
                <tr>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        {{ \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($tx->transaction_date))->format('Y/m/d') }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $tx->direction === 'credit' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                            {{ $tx->getTypeLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $tx->description }}</td>
                    <td class="px-5 py-3 font-semibold {{ $tx->direction === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $tx->direction === 'credit' ? '+' : '-' }}{{ number_format($tx->amount) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center text-gray-400">هیچ تراکنشی ثبت نشده است</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
    </div>

    {{-- Payment Modal --}}
    @if($showPaymentModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.outside="$set('showPaymentModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">ثبت پرداخت - {{ $unit->number }}</h3>
                <button wire:click="$set('showPaymentModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="savePayment" class="p-6 space-y-4">
                <x-form-input wire:model="pay_amount" label="مبلغ (تومان)" type="number" min="1" required/>
                <x-form-input wire:model="pay_date" label="تاریخ پرداخت" type="date" required/>
                <x-form-input wire:model="pay_tracking" label="کد پیگیری" placeholder="اختیاری"/>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رسید پرداخت</label>
                    <input wire:model="receipt" type="file" accept="image/*,.pdf"
                        class="w-full text-sm text-gray-500 file:ml-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0f766e]/10 file:text-[#0f766e] hover:file:bg-[#0f766e]/20"/>
                    @error('receipt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>ثبت پرداخت</span>
                        <span wire:loading>در حال ذخیره...</span>
                    </button>
                    <button type="button" wire:click="$set('showPaymentModal', false)"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition-colors">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
