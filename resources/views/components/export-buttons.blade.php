@props(['excel', 'pdf', 'image', 'imageName' => 'report.png'])

<div class="flex gap-2">
    <a href="{{ $excel }}"
       class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>اکسل</a>
    <a href="{{ $pdf }}"
       class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>PDF</a>
    <button type="button" onclick="exportReportImage('{{ $image }}','{{ $imageName }}')"
            class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[11px] border border-[#ececef] bg-white text-[12px] font-bold text-[#3f3f46]"><span class="text-[14px]">⬇</span>تصویر</button>
</div>
