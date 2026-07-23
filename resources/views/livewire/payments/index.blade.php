<div class="space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="شماره واحد..."
                    class="pr-9 pl-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] w-48"/>
                <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select wire:model.live="building_id_filter"
                class="py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                <option value="">همه ساختمان‌ها</option>
                @foreach($buildings as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="openCreate"
            class="flex items-center gap-2 bg-[#0f766e] hover:bg-[#0f5f58] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            ثبت پرداخت
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">تاریخ</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">واحد</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ساختمان</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">مبلغ</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">کد پیگیری</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">ثبت توسط</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5 text-gray-500 text-xs">
                        {{ \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($payment->payment_date))->format('Y/m/d') }}
                    </td>
                    <td class="px-5 py-3.5">
                        <a href="{{ route('units.show', $payment->unit) }}" class="text-[#0f766e] hover:underline font-medium">
                            {{ $payment->unit->number }}
                        </a>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $payment->building->name }}</td>
                    <td class="px-5 py-3.5 font-bold text-green-600">+{{ number_format($payment->amount) }} ت</td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs ltr">{{ $payment->tracking_number ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $payment->creator->name }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-400">هیچ پرداختی ثبت نشده است</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $payments->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.outside="$set('showModal', false)">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">ثبت پرداخت</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">واحد <span class="text-red-500">*</span></label>
                    <select wire:model="unit_id"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0f766e] bg-white">
                        <option value="">انتخاب واحد</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}">{{ $u->building->name }} - {{ $u->number }}</option>
                        @endforeach
                    </select>
                    @error('unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-input wire:model="amount" label="مبلغ (تومان)" type="number" min="1" required/>
                    <x-form-input wire:model="payment_date" label="تاریخ پرداخت" type="date" required/>
                </div>
                <x-form-input wire:model="tracking_number" label="کد پیگیری" placeholder="اختیاری"/>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رسید پرداخت</label>
                    <input wire:model="receipt" type="file" accept="image/*,.pdf"
                        class="w-full text-sm text-gray-500 file:ml-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0f766e]/10 file:text-[#0f766e] hover:file:bg-[#0f766e]/20"/>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#0f766e] hover:bg-[#0f5f58] text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>ثبت پرداخت</span>
                        <span wire:loading>در حال ذخیره...</span>
                    </button>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
