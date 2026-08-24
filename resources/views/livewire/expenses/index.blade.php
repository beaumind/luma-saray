@php
    use App\Support\Fmt;
    $distLabels = ['fund' => 'از صندوق', 'all_units' => 'همه واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'];
@endphp
<div>
    <x-app-header title="هزینه‌ها" :back="route('dashboard')">
        <x-slot:action>
            <button wire:click="openCreate" type="button"
                    class="flex h-[34px] items-center gap-1.5 rounded-[10px] bg-[#5b5bd6] px-[13px] text-[13px] font-bold text-white">
                <span class="text-[15px] leading-none">＋</span>ثبت هزینه
            </button>
        </x-slot:action>
    </x-app-header>

    <div class="px-4 pt-4">
        {{-- Filters + export --}}
        <div class="mb-3 flex flex-col gap-2.5 rounded-[14px] border border-[#ececef] bg-white p-3">
            <div class="flex gap-2.5">
                <div class="flex-1"><x-jalali-date-input wire:model.live="from" label="از تاریخ" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model.live="to" label="تا تاریخ" /></div>
            </div>
            @if($buildings->count() > 1)
                <select wire:model.live="building_id_filter" class="h-[42px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[13px] outline-none focus:border-[#5b5bd6]">
                    <option value="">همهٔ ساختمان‌ها</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
            @endif
            <div class="flex gap-2">
                <a href="{{ route('expenses.export.excel', $exportParams) }}"
                   class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>اکسل</a>
                <a href="{{ route('expenses.export.pdf', $exportParams) }}"
                   class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>PDF</a>
                <button type="button" onclick="exportReportImage('#expenses-print','costs.png')"
                        class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>تصویر</button>
            </div>
        </div>

        <div class="flex flex-col gap-[9px]">
            @forelse($expenses as $e)
                @php
                    $col = $e->category->color ?? '#5b5bd6';
                    $dist = ['fund' => 'از صندوق', 'all_units' => 'همه واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'][$e->distribution] ?? $e->distribution;
                @endphp
                <button wire:click="openDetail({{ $e->id }})" type="button" class="flex w-full items-center gap-3 rounded-[14px] border border-[#ececef] bg-white px-3.5 py-[13px] text-right">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] text-[15px] font-bold" style="background:{{ $col }}1a;color:{{ $col }}">{{ mb_substr($e->category->name ?? 'ه', 0, 1) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[13.5px] font-bold text-[#18181b]">{{ $e->title }}</div>
                        <div class="text-[11.5px] text-[#a1a1aa]">{{ $e->category->name ?? 'بدون دسته' }} · <x-jdate :value="$e->expense_date" /> · {{ $dist }}</div>
                    </div>
                    <div class="text-[13.5px] font-bold text-[#dc2626]">{{ Fmt::money($e->amount) }}</div>
                </button>
            @empty
                <div class="rounded-[14px] border border-[#ececef] bg-white px-4 py-10 text-center text-[13px] text-[#a1a1aa]">هزینه‌ای ثبت نشده است</div>
            @endforelse
        </div>
        @if($expenses->hasPages())<div class="mt-4">{{ $expenses->links() }}</div>@endif
    </div>

    <x-sheet model="showModal" :title="$editingId ? 'ویرایش هزینه' : 'ثبت هزینهٔ جدید'">
        <form wire:submit="save" class="flex flex-col gap-3">
            <x-input wire:model="title" label="عنوان هزینه" />
            <div class="flex gap-2.5">
                <div class="flex-1"><x-money-input wire:model="amount" label="مبلغ ({{ \App\Support\Fmt::currency() }})" /></div>
                <div class="flex-1"><x-jalali-date-input wire:model="expense_date" label="تاریخ" /></div>
            </div>

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">ساختمان</span>
                <select wire:model.live="building_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">انتخاب ساختمان…</option>
                    @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
                @error('building_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </label>

            <label class="flex flex-col gap-1.5">
                <span class="text-[12.5px] font-semibold text-[#3f3f46]">دسته‌بندی</span>
                <select wire:model="expense_category_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                    <option value="">بدون دسته</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </label>

            {{-- Funding: who bears this cost --}}
            <div>
                <span class="mb-1.5 block text-[12.5px] font-semibold text-[#3f3f46]">این هزینه بر عهدهٔ کیست؟</span>
                <div class="flex flex-col gap-2">
                    @php
                        $opts = [
                            'fund' => ['شارژ / صندوق ساختمان', 'هزینهٔ پیش‌بینی‌شده که از صندوق پرداخت می‌شود'],
                            'all_units' => ['تقسیم بین همهٔ واحدها', 'هزینهٔ پیش‌بینی‌نشده، سهم هر واحد به بدهی‌اش اضافه می‌شود'],
                            'single_unit' => ['یک واحد مشخص', 'مثل خرابی درب یک واحد — فقط همان واحد بدهکار می‌شود'],
                        ];
                    @endphp
                    @foreach($opts as $key => [$t, $d])
                        @php $on = $distribution === $key; @endphp
                        <button type="button" wire:click="$set('distribution','{{ $key }}')"
                                class="flex items-start gap-2.5 rounded-[12px] border-[1.5px] px-3 py-2.5 text-right"
                                style="border-color:{{ $on ? '#5b5bd6' : '#ececef' }};background:{{ $on ? '#f6f6fd' : '#fff' }}">
                            <span class="mt-0.5 flex h-[18px] w-[18px] flex-none items-center justify-center rounded-full border-2" style="border-color:{{ $on ? '#5b5bd6' : '#d4d4d8' }}">
                                <span class="h-2 w-2 rounded-full" style="background:{{ $on ? '#5b5bd6' : 'transparent' }}"></span>
                            </span>
                            <span class="flex-1"><span class="block text-[13px] font-bold text-[#18181b]">{{ $t }}</span><span class="mt-0.5 block text-[11.5px] text-[#71717a]">{{ $d }}</span></span>
                        </button>
                    @endforeach
                </div>
                @error('distribution')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
            </div>

            @if($distribution === 'single_unit')
                <label class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">کدام واحد؟</span>
                    <select wire:model="single_unit_id" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="">انتخاب واحد…</option>
                        @foreach($units as $u)<option value="{{ $u->id }}">واحد {{ Fmt::fa($u->number) }}</option>@endforeach
                    </select>
                    @error('single_unit_id')<span class="text-[11.5px] text-[#dc2626]">{{ $message }}</span>@enderror
                </label>
            @endif

            @if($distribution !== 'fund')
                <label class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-semibold text-[#3f3f46]">مسئول پرداخت</span>
                    <select wire:model="responsible" class="h-[46px] rounded-[11px] border border-[#e4e4e7] bg-[#fafafa] px-[13px] text-[14px] outline-none focus:border-[#5b5bd6]">
                        <option value="owner">مالک</option><option value="tenant">مستأجر</option><option value="both">هردو</option>
                    </select>
                </label>
            @endif

            <x-input wire:model="description" label="توضیحات (اختیاری)" />

            <x-resumable-upload model="image_path" folder="expenses" label="تصویر فاکتور (اختیاری)" />

            <x-submit-button target="save" class="mt-1 h-[50px] rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">ثبت هزینه</x-submit-button>
            <p class="text-center text-[11px] text-[#a1a1aa]">پرداخت این هزینه را از بخش «پرداخت‌ها» ثبت کنید.</p>
        </form>
    </x-sheet>

    {{-- Cost detail --}}
    <x-sheet model="showDetail" title="جزئیات هزینه">
        @if($detailExpense)
            @php
                $distLabel = ['fund' => 'از صندوق', 'all_units' => 'تقسیم بین همهٔ واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'][$detailExpense->distribution] ?? $detailExpense->distribution;
                $respLabel = ['owner' => 'مالک', 'tenant' => 'مستأجر', 'both' => 'هردو'][$detailExpense->responsible] ?? null;
            @endphp
            <div class="flex flex-col gap-3">
                <div class="rounded-[14px] bg-[#f7f7f8] p-3.5 text-center">
                    <div class="text-[12px] text-[#71717a]">{{ $detailExpense->title }}</div>
                    <div class="mt-1 text-[24px] font-extrabold text-[#dc2626]">{{ Fmt::money($detailExpense->amount) }} <span class="text-[13px] font-semibold text-[#71717a]">{{ Fmt::currency() }}</span></div>
                </div>
                @php
                    $rows = array_filter([
                        ['ساختمان', $detailExpense->building?->name],
                        $detailExpense->category ? ['دسته‌بندی', $detailExpense->category->name] : null,
                        ['تاریخ', \App\Support\JDate::toJalali($detailExpense->expense_date)],
                        ['تقسیم هزینه', $distLabel],
                        $detailExpense->distribution !== 'fund' && $respLabel ? ['مسئول پرداخت', $respLabel] : null,
                        $detailExpense->description ? ['توضیحات', $detailExpense->description] : null,
                    ]);
                @endphp
                @foreach($rows as [$k, $v])
                    <div class="flex justify-between border-b border-[#f4f4f5] pb-2.5 text-[13px]">
                        <span class="text-[#71717a]">{{ $k }}</span>
                        <span class="font-semibold text-[#18181b]">{{ $v }}</span>
                    </div>
                @endforeach

                @if($detailExpense->distribution !== 'fund' && $detailExpense->expenseUnits->isNotEmpty())
                    <div>
                        <div class="mb-1.5 text-[12.5px] font-semibold text-[#3f3f46]">سهم واحدها</div>
                        <div class="flex flex-col gap-1.5">
                            @foreach($detailExpense->expenseUnits as $eu)
                                <div class="flex justify-between rounded-[10px] bg-[#fafafa] px-3 py-2 text-[12.5px]">
                                    <span class="text-[#3f3f46]">واحد {{ Fmt::fa($eu->unit?->number) }}</span>
                                    <span class="font-semibold text-[#18181b]">{{ Fmt::money($eu->amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($detailExpense->attachments))
                    <div>
                        <div class="mb-1.5 text-[12.5px] font-semibold text-[#3f3f46]">فاکتور / ضمیمه</div>
                        <div class="flex flex-col gap-2">
                            @foreach($detailExpense->attachments as $att)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($att) }}" target="_blank">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($att) }}" alt="فاکتور"
                                         class="max-h-[280px] w-full rounded-[12px] border border-[#ececef] object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                                    <span style="display:none" class="block rounded-[10px] bg-[#eef0fb] px-3 py-2 text-center text-[12.5px] text-[#5b5bd6]">باز کردن فایل ضمیمه</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <button type="button" wire:click="editFromDetail" class="mt-1 h-[48px] rounded-[13px] border-[1.5px] border-[#5b5bd6] text-[14px] font-bold text-[#5b5bd6]">ویرایش هزینه</button>
            </div>
        @endif
    </x-sheet>

    {{-- Off-screen snapshot used by the image export --}}
    <div id="expenses-print" aria-hidden="true" dir="rtl"
         style="position:absolute;left:-9999px;top:0;width:840px;background:#fff;padding:22px;font-family:Vazirmatn,sans-serif;color:#18181b">
        <div style="text-align:center;font-size:17px;font-weight:800;margin-bottom:4px">گزارش هزینه‌ها</div>
        <div style="text-align:center;font-size:12px;color:#71717a;margin-bottom:14px">تعداد: {{ Fmt::fa($printRows->count()) }} — مجموع: {{ Fmt::money($printRows->sum('amount')) }} {{ Fmt::currency() }}</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead>
                <tr style="background:#5b5bd6;color:#fff">
                    <th style="border:1px solid #cfcfd6;padding:7px">عنوان</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">ساختمان</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">دسته</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">تاریخ</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">مبلغ</th>
                    <th style="border:1px solid #cfcfd6;padding:7px">تقسیم</th>
                </tr>
            </thead>
            <tbody>
                @foreach($printRows as $e)
                    <tr>
                        <td style="border:1px solid #e4e4e7;padding:6px">{{ $e->title }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $e->building?->name ?? '—' }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $e->category?->name ?? '—' }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center"><x-jdate :value="$e->expense_date" /></td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center;color:#dc2626;font-weight:700">{{ Fmt::money($e->amount) }}</td>
                        <td style="border:1px solid #e4e4e7;padding:6px;text-align:center">{{ $distLabels[$e->distribution] ?? $e->distribution }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
