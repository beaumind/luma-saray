<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Support\Fmt;
use App\Support\JDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel / PDF exports for the costs (Expenses) and payments lists, honouring the
 * same building + Jalali date-range filters as the on-screen list. The cost PDF
 * embeds the invoice image and any related payments (with their receipts).
 */
class LedgerExportController extends Controller
{
    // ---- Costs (Expenses) -------------------------------------------------

    public function expensesExcel(Request $request): StreamedResponse
    {
        $rows = $this->expenseQuery($request)->get();

        $headers = ['عنوان', 'ساختمان', 'دسته‌بندی', 'تاریخ', 'مبلغ ('.Fmt::currency().')', 'تقسیم', 'پرداخت‌شده', 'ضمیمه', 'توضیحات'];
        $dist = ['fund' => 'از صندوق', 'all_units' => 'همه واحدها', 'single_unit' => 'یک واحد', 'selected_units' => 'واحدهای منتخب'];

        $data = $rows->map(function (Expense $e) use ($dist) {
            $paid = Payment::where('expense_id', $e->id)->sum('amount');

            return [
                'عنوان' => $e->title,
                'ساختمان' => $e->building?->name ?? '—',
                'دسته‌بندی' => $e->category?->name ?? '—',
                'تاریخ' => JDate::toJalali($e->expense_date),
                'مبلغ' => number_format(Fmt::display((int) $e->amount)),
                'تقسیم' => $dist[$e->distribution] ?? $e->distribution,
                'پرداخت‌شده' => $paid > 0 ? number_format(Fmt::display((int) $paid)) : '—',
                'ضمیمه' => $this->firstAttachmentUrl($e->attachments),
                'توضیحات' => $e->description ?? '',
            ];
        })->all();

        return $this->streamXlsx($data, $headers, 'گزارش هزینه‌ها', 'costs', linkColumn: 'ضمیمه');
    }

    public function expensesPdf(Request $request)
    {
        $rows = $this->expenseQuery($request)
            ->with(['building', 'category', 'expenseUnits.unit'])
            ->get()
            ->each(function (Expense $e) {
                $e->setRelation('relatedPayments', Payment::with('unit')->where('expense_id', $e->id)->orderBy('payment_date')->get());
            });

        $html = view('exports.expenses', [
            'rows' => $rows,
            'title' => 'گزارش هزینه‌ها'.$this->rangeLabel($request),
            'localPath' => fn (?string $p) => $this->localImage($p),
        ])->render();

        return $this->streamPdf($html, 'costs');
    }

    // ---- Payments ---------------------------------------------------------

    public function paymentsExcel(Request $request): StreamedResponse
    {
        $rows = $this->paymentQuery($request)->with(['unit.building', 'expense'])->get();

        $headers = ['نوع', 'واحد', 'ساختمان', 'هزینهٔ مرتبط', 'مبلغ ('.Fmt::currency().')', 'تاریخ', 'شماره پیگیری', 'رسید', 'توضیحات'];
        $types = ['charge' => 'شارژ', 'fund_cost' => 'پرداخت از صندوق', 'unit_cost' => 'هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد'];

        $data = $rows->map(fn (Payment $p) => [
            'نوع' => $types[$p->type] ?? $p->type,
            'واحد' => $p->unit ? Fmt::fa($p->unit->number) : '—',
            'ساختمان' => $p->unit?->building?->name ?? '—',
            'هزینهٔ مرتبط' => $p->expense?->title ?? '—',
            'مبلغ' => number_format(Fmt::display((int) $p->amount)),
            'تاریخ' => JDate::toJalali($p->payment_date),
            'شماره پیگیری' => $p->tracking_number ?? '—',
            'رسید' => $p->receipt_path ? Storage::disk('public')->url($p->receipt_path) : '',
            'توضیحات' => $p->notes ?? '',
        ])->all();

        return $this->streamXlsx($data, $headers, 'گزارش پرداخت‌ها', 'payments', linkColumn: 'رسید');
    }

    public function paymentsPdf(Request $request)
    {
        $rows = $this->paymentQuery($request)->with(['unit.building', 'expense'])->get();

        $html = view('exports.payments', [
            'rows' => $rows,
            'title' => 'گزارش پرداخت‌ها'.$this->rangeLabel($request),
            'localPath' => fn (?string $p) => $this->localImage($p),
        ])->render();

        return $this->streamPdf($html, 'payments');
    }

    // ---- Query builders (shared filters) ----------------------------------

    private function expenseQuery(Request $request): Builder
    {
        return Expense::query()
            ->when($request->integer('building') ?: null, fn ($q, $b) => $q->where('building_id', $b))
            ->when($this->from($request), fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($this->to($request), fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->orderByDesc('expense_date')->orderByDesc('id');
    }

    private function paymentQuery(Request $request): Builder
    {
        return Payment::query()
            ->when($request->integer('building') ?: null, fn ($q, $b) => $q->where('building_id', $b))
            ->when($this->from($request), fn ($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($this->to($request), fn ($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->orderByDesc('payment_date')->orderByDesc('id');
    }

    private function from(Request $request): ?string
    {
        return JDate::toGregorian((string) $request->query('from')) ?: null;
    }

    private function to(Request $request): ?string
    {
        return JDate::toGregorian((string) $request->query('to')) ?: null;
    }

    private function rangeLabel(Request $request): string
    {
        $from = (string) $request->query('from');
        $to = (string) $request->query('to');

        return $from || $to ? ' — از '.($from ? Fmt::fa($from) : '…').' تا '.($to ? Fmt::fa($to) : '…') : '';
    }

    // ---- Attachments ------------------------------------------------------

    private function firstAttachmentUrl(mixed $attachments): string
    {
        $first = is_array($attachments) ? ($attachments[0] ?? null) : null;

        return $first ? Storage::disk('public')->url($first) : '';
    }

    /** Absolute local path for mPDF, only for image files that exist. */
    private function localImage(?string $path): ?string
    {
        if (! $path || ! preg_match('/\.(jpe?g|png|gif|webp)$/i', $path)) {
            return null;
        }
        $abs = Storage::disk('public')->path($path);

        return is_file($abs) ? $abs : null;
    }

    // ---- Writers ----------------------------------------------------------

    private function streamXlsx(array $data, array $headers, string $sheetTitle, string $slug, ?string $linkColumn = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle(mb_substr($sheetTitle, 0, 30));

        $letters = [];
        foreach (range(1, count($headers)) as $i) {
            $letters[] = Coordinate::stringFromColumnIndex($i);
        }
        $last = end($letters);

        foreach ($headers as $i => $label) {
            $sheet->setCellValue($letters[$i].'1', $label);
        }
        $head = $sheet->getStyle("A1:{$last}1");
        $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('5B5BD6');
        $head->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = 2;
        foreach ($data as $row) {
            $values = array_values($row);
            foreach ($values as $i => $val) {
                $coord = $letters[$i].$r;
                if ($linkColumn !== null && $headers[$i] === $linkColumn && $val) {
                    $sheet->setCellValue($coord, 'مشاهده');
                    $sheet->getCell($coord)->getHyperlink()->setUrl($val);
                    $sheet->getStyle($coord)->getFont()->getColor()->setRGB('5B5BD6');
                } else {
                    $sheet->setCellValueExplicit($coord, (string) $val, DataType::TYPE_STRING);
                }
                $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $r++;
        }

        foreach ($letters as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(18);
        }
        if ($r > 2) {
            $sheet->getStyle("A1:{$last}".($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $filename = $slug.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function streamPdf(string $html, string $slug)
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);

        $filename = $slug.'-'.now()->format('Ymd-His').'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
