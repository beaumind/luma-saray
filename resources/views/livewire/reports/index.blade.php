@php
    use App\Support\Fmt;
    $stateBg = ['paid' => '#dcfce7', 'partial' => '#fef9c3', 'unpaid' => '#fee2e2', 'neutral' => '#ffffff'];
    $stateFg = ['paid' => '#16a34a', 'partial' => '#b45309', 'unpaid' => '#dc2626', 'neutral' => '#71717a'];
    $visibleCols = collect($matrix['columns'])->filter(fn($c) => in_array($c['key'], ['number','resident']) || in_array($c['key'], $cols));
@endphp
<div>
    <x-app-header title="گزارش‌ها" :back="route('dashboard')" />

    <div class="flex flex-col gap-3.5 px-4 pt-4">

        {{-- Filters --}}
        <div class="flex flex-col gap-2">
            <select wire:model.live="building_id" class="h-9 w-full rounded-[10px] border border-[#ececef] bg-white px-2.5 text-[12.5px] outline-none">
                <option value="">همه ساختمان‌ها</option>
                @foreach($buildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
            </select>
            <div class="flex gap-2">
                <select wire:model.live="periodType" class="h-9 flex-1 rounded-[10px] border border-[#ececef] bg-white px-2.5 text-[12.5px] outline-none">
                    @foreach($periodTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                <select wire:model.live="count" class="h-9 flex-1 rounded-[10px] border border-[#ececef] bg-white px-2.5 text-[12.5px] outline-none">
                    @foreach($countOptions as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>

        {{-- Summary card --}}
        <div class="rounded-[16px] border border-[#ececef] bg-white p-4">
            <div class="mb-3.5 flex items-center justify-between">
                <div class="text-[14px] font-bold text-[#18181b]">گزارش سال {{ $yearLabel }}</div>
                <span class="text-[11px] text-[#a1a1aa]">{{ Fmt::fa(count($matrix['rows'])) }} واحد</span>
            </div>
            <div class="flex flex-col gap-3">
                @foreach($summary as $r)
                    <div>
                        <div class="mb-1.5 flex justify-between text-[12.5px]">
                            <span class="font-semibold text-[#3f3f46]">{{ $r['label'] }}</span>
                            <span class="font-bold text-[#18181b]">{{ Fmt::money($r['value']) }}</span>
                        </div>
                        <div class="h-[7px] overflow-hidden rounded-full bg-[#f4f4f5]">
                            <div class="h-full rounded-full" style="width:{{ $r['pct'] }}%;background:{{ $r['color'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Column customization --}}
        <div class="rounded-[16px] border border-[#ececef] bg-white">
            <button wire:click="$toggle('showColumns')" type="button" class="flex w-full items-center justify-between px-4 py-3.5">
                <span class="text-[13.5px] font-bold text-[#18181b]">ستون‌های گزارش</span>
                <span class="text-[12px] text-[#5b5bd6]">{{ $showColumns ? 'بستن' : 'سفارشی‌سازی' }}</span>
            </button>
            @if($showColumns)
                <div class="flex flex-wrap gap-2 border-t border-[#f4f4f5] px-4 py-3.5">
                    @foreach($matrix['columns'] as $col)
                        @continue(in_array($col['key'], ['number', 'resident']))
                        <label class="flex items-center gap-1.5 rounded-full border border-[#ececef] px-2.5 py-1 text-[12px]">
                            <input type="checkbox" wire:model.live="cols" value="{{ $col['key'] }}" class="h-3.5 w-3.5 rounded text-[#5b5bd6]">
                            {{ $col['label'] }}
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Debt matrix --}}
        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            <div class="border-b border-[#f4f4f5] px-4 py-3 text-[13.5px] font-bold text-[#18181b]">جدول بدهی واحدها</div>
            <div class="overflow-x-auto">
                <table id="debt-matrix" class="w-full border-collapse text-[11px]" style="min-width:max-content">
                    <thead>
                        <tr class="bg-[#5b5bd6] text-white">
                            @foreach($visibleCols as $col)
                                <th class="whitespace-nowrap border border-[#e4e4ec] px-2.5 py-2 font-bold">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matrix['rows'] as $row)
                            <tr>
                                @foreach($visibleCols as $col)
                                    @php
                                        $key = $col['key'];
                                        $bg = '#ffffff'; $fg = '#18181b'; $val = '';
                                        if ($key === 'number') { $val = Fmt::fa($row['number']); }
                                        elseif ($key === 'resident') { $val = $row['resident']; }
                                        elseif ($key === 'owner') { $val = $row['owner']; }
                                        elseif ($key === 'count') { $val = Fmt::fa($row['count']); }
                                        elseif ($key === 'monthly_charge') { $val = Fmt::money($row['monthly_charge']); }
                                        elseif ($key === 'past_debt') { $val = Fmt::money($row['past_debt']); $bg = $row['past_debt']>0 ? '#ffedd5' : '#dcfce7'; }
                                        elseif ($key === 'total_debt') { $val = Fmt::money($row['total_debt']); $bg = $row['total_debt']>0 ? '#fee2e2':'#dcfce7'; $fg = $row['total_debt']>0?'#dc2626':'#16a34a'; }
                                        elseif ($key === 'notes') { $val = $row['notes']; }
                                        elseif (str_starts_with($key, 'month_')) {
                                            $cell = $row['months'][$col['month']];
                                            $val = Fmt::money($cell['value']); $bg = $stateBg[$cell['state']]; $fg = $stateFg[$cell['state']];
                                        }
                                    @endphp
                                    <td class="whitespace-nowrap border border-[#eee] px-2.5 py-2 text-center font-semibold" style="background:{{ $bg }};color:{{ $fg }}">{{ $val !== '' ? $val : '—' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ $visibleCols->count() }}" class="px-4 py-8 text-center text-[#a1a1aa]">داده‌ای برای نمایش نیست</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Export buttons --}}
        <div class="flex gap-2.5">
            <a href="{{ route('reports.export.excel', $exportParams) }}"
               class="flex h-11 flex-1 flex-col items-center justify-center gap-0.5 rounded-[12px] border border-[#ececef] bg-white text-[12.5px] font-bold text-[#3f3f46]">
                <span class="text-[15px]">⬇</span>اکسل
            </a>
            <a href="{{ route('reports.export.pdf', $exportParams) }}"
               class="flex h-11 flex-1 flex-col items-center justify-center gap-0.5 rounded-[12px] border border-[#ececef] bg-white text-[12.5px] font-bold text-[#3f3f46]">
                <span class="text-[15px]">⬇</span>PDF
            </a>
            <button type="button" onclick="exportReportImage('#debt-matrix','debt-report.png')"
                    class="flex h-11 flex-1 flex-col items-center justify-center gap-0.5 rounded-[12px] border border-[#ececef] bg-white text-[12.5px] font-bold text-[#3f3f46]">
                <span class="text-[15px]">⬇</span>تصویر
            </button>
        </div>
    </div>
</div>
