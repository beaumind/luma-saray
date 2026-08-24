<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsExports;
use App\Models\Building;
use App\Models\Resident;
use App\Models\Unit;
use App\Support\Fmt;
use App\Support\JDate;
use Illuminate\Http\Request;

/**
 * Excel / PDF exports for the units and residents directories, honouring the
 * same building (and, for residents, type) filters as the on-screen lists.
 */
class RosterExportController extends Controller
{
    use StreamsExports;

    // ---- Units ------------------------------------------------------------

    public function unitsExcel(Request $request)
    {
        $rows = $this->units($request);
        $headers = ['واحد', 'طبقه', 'ساختمان', 'مالک', 'ساکن', 'تعداد نفرات', 'وضعیت', 'مانده حساب ('.Fmt::currency().')'];

        $data = $rows->map(fn (Unit $u) => $this->unitRow($u))->all();

        return $this->streamXlsx($data, $headers, 'گزارش واحدها', 'units');
    }

    public function unitsPdf(Request $request)
    {
        $rows = $this->units($request)->map(fn (Unit $u) => $this->unitRow($u));

        $html = view('exports.table', [
            'title' => 'گزارش واحدها'.$this->buildingLabel($request),
            'headers' => ['واحد', 'طبقه', 'ساختمان', 'مالک', 'ساکن', 'تعداد نفرات', 'وضعیت', 'مانده حساب'],
            'rows' => $rows,
        ])->render();

        return $this->streamPdf($html, 'units');
    }

    // ---- Residents --------------------------------------------------------

    public function residentsExcel(Request $request)
    {
        $rows = $this->residents($request);
        $headers = ['نام', 'واحد', 'ساختمان', 'نوع', 'موبایل', 'کد ملی', 'تعداد نفرات', 'تاریخ ورود', 'تاریخ خروج'];

        $data = $rows->map(fn (Resident $r) => $this->residentRow($r))->all();

        return $this->streamXlsx($data, $headers, 'گزارش ساکنان', 'residents');
    }

    public function residentsPdf(Request $request)
    {
        $rows = $this->residents($request)->map(fn (Resident $r) => $this->residentRow($r));

        $html = view('exports.table', [
            'title' => 'گزارش ساکنان'.$this->buildingLabel($request),
            'headers' => ['نام', 'واحد', 'ساختمان', 'نوع', 'موبایل', 'کد ملی', 'تعداد نفرات', 'تاریخ ورود', 'تاریخ خروج'],
            'rows' => $rows,
        ])->render();

        return $this->streamPdf($html, 'residents');
    }

    // ---- Data -------------------------------------------------------------

    private function units(Request $request)
    {
        return Unit::query()
            ->where('is_active', true)
            ->when($request->integer('building') ?: null, fn ($q, $b) => $q->where('building_id', $b))
            ->with(['building', 'activeResidents'])
            ->orderBy('building_id')->orderByRaw('LENGTH(number)')->orderBy('number')
            ->get();
    }

    private function residents(Request $request)
    {
        return Resident::query()
            ->where('is_active', true)
            ->when($request->integer('building') ?: null, fn ($q, $b) => $q->whereHas('unit', fn ($uq) => $uq->where('building_id', $b)))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->with(['unit.building'])
            ->orderBy(Unit::select('number')->whereColumn('units.id', 'residents.unit_id'))
            ->orderByRaw("CASE type WHEN 'owner' THEN 0 ELSE 1 END")
            ->get();
    }

    private function unitRow(Unit $u): array
    {
        $owner = $u->activeResidents->firstWhere('type', 'owner');
        $occupant = $u->activeResidents->sortByDesc('resident_count')->first();
        $persons = (int) $u->activeResidents->sum('resident_count');

        return [
            'واحد' => Fmt::fa($u->number),
            'طبقه' => $u->floor !== null ? Fmt::fa($u->floor) : '—',
            'ساختمان' => $u->building?->name ?? '—',
            'مالک' => $owner?->name ?? '—',
            'ساکن' => $occupant?->name ?? '—',
            'تعداد نفرات' => Fmt::fa($persons),
            'وضعیت' => $persons > 0 ? 'سکونت' : 'خالی',
            'مانده حساب' => number_format(Fmt::display((int) $u->balance)),
        ];
    }

    private function residentRow(Resident $r): array
    {
        return [
            'نام' => $r->name,
            'واحد' => $r->unit ? Fmt::fa($r->unit->number) : '—',
            'ساختمان' => $r->unit?->building?->name ?? '—',
            'نوع' => $r->type === 'owner' ? 'مالک' : 'مستأجر',
            'موبایل' => $r->mobile ? Fmt::fa($r->mobile) : '—',
            'کد ملی' => $r->national_code ? Fmt::fa($r->national_code) : '—',
            'تعداد نفرات' => Fmt::fa((int) $r->resident_count),
            'تاریخ ورود' => $r->move_in_date ? JDate::toJalali($r->move_in_date) : '—',
            'تاریخ خروج' => $r->move_out_date ? JDate::toJalali($r->move_out_date) : '—',
        ];
    }

    private function buildingLabel(Request $request): string
    {
        $id = $request->integer('building') ?: null;

        return $id ? ' — '.(Building::find($id)?->name ?? '') : '';
    }
}
