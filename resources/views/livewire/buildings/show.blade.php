@php use App\Support\Fmt; @endphp
<div>
    <x-app-header :title="$building->name" :back="route('buildings.index')" />

    <div class="flex flex-col gap-3.5 px-4 pt-4">
        <div class="flex h-[120px] items-center justify-center rounded-[16px] border border-[#ececef] font-mono text-[12px] text-[#8b8bd6]"
             style="background:repeating-linear-gradient(135deg,#eef0fb,#eef0fb 10px,#f5f6fc 10px,#f5f6fc 20px)">🏢 {{ $building->name }}</div>

        <div class="overflow-hidden rounded-[16px] border border-[#ececef] bg-white">
            @php
                $rows = [
                    ['نام ساختمان', $building->name],
                    ['شهر', $building->city],
                    ['آدرس', $building->address],
                    ['تعداد طبقات', Fmt::fa($building->floors).' طبقه'],
                    ['تعداد واحدها', Fmt::fa($building->units->count()).' واحد'],
                    ['مدیر ساختمان', $building->manager_name ?: '—'],
                    ['موبایل مدیر', $building->manager_mobile ? Fmt::fa($building->manager_mobile) : '—'],
                ];
            @endphp
            @foreach($rows as [$k, $v])
                <div class="flex justify-between border-b border-[#f7f7f8] px-[15px] py-[13px]">
                    <span class="text-[13px] text-[#71717a]">{{ $k }}</span>
                    <span class="text-[13px] font-bold text-[#18181b]">{{ $v }}</span>
                </div>
            @endforeach
        </div>

        <a href="{{ route('units.index', ['building_id' => $building->id]) }}" wire:navigate
           class="flex h-12 items-center justify-center rounded-[13px] bg-[#5b5bd6] text-[15px] font-bold text-white">مشاهدهٔ واحدها</a>
    </div>
</div>
