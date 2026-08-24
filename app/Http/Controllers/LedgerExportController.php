<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsExports;
use App\Models\Expense;
use App\Models\Payment;
use App\Support\Fmt;
use App\Support\JDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel / PDF exports for the costs (Expenses) and payments lists, honouring the
 * same building + Jalali date-range filters as the on-screen list. Costs export
 * as an organised table; the invoice image is shown as a thumbnail column.
 */
class LedgerExportController extends Controller
{
    use StreamsExports;

    // ---- Costs (Expenses) -------------------------------------------------

    public function expensesExcel(Request $request): StreamedResponse
    {
        $rows = $this->expenseQuery($request)->get();

        $headers = ['عنوان', 'ساختمان', 'دسته‌بندی', 'تاریخ', 'مبلغ ('.Fmt::currency().')', 'تقسیم', 'پرداخت‌شده', 'توضیحات'];
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
                'توضیحات' => $e->description ?? '',
            ];
        })->all();

        return $this->streamXlsx($data, $headers, 'گزارش هزینه‌ها', 'costs');
    }

    public function expensesPdf(Request $request)
    {
        $rows = $this->expenseQuery($request)->with(['building', 'category'])->get();

        $html = view('exports.expenses', [
            'rows' => $rows,
            'title' => 'گزارش هزینه‌ها'.$this->rangeLabel($request),
        ])->render();

        return $this->streamPdf($html, 'costs');
    }

    // ---- Payments ---------------------------------------------------------

    public function paymentsExcel(Request $request): StreamedResponse
    {
        $rows = $this->paymentQuery($request)->with(['unit.building', 'expense'])->get();

        $headers = ['نوع', 'واحد', 'ساختمان', 'هزینهٔ مرتبط', 'مبلغ ('.Fmt::currency().')', 'تاریخ', 'شماره پیگیری', 'توضیحات'];
        $types = ['charge' => 'شارژ', 'fund_cost' => 'پرداخت از صندوق', 'unit_cost' => 'هزینهٔ واحد', 'unit_credit' => 'بستانکاری واحد'];

        $data = $rows->map(fn (Payment $p) => [
            'نوع' => $types[$p->type] ?? $p->type,
            'واحد' => $p->unit ? Fmt::fa($p->unit->number) : '—',
            'ساختمان' => $p->unit?->building?->name ?? '—',
            'هزینهٔ مرتبط' => $p->expense?->title ?? '—',
            'مبلغ' => number_format(Fmt::display((int) $p->amount)),
            'تاریخ' => JDate::toJalali($p->payment_date),
            'شماره پیگیری' => $p->tracking_number ?? '—',
            'توضیحات' => $p->notes ?? '',
        ])->all();

        return $this->streamXlsx($data, $headers, 'گزارش پرداخت‌ها', 'payments');
    }

    public function paymentsPdf(Request $request)
    {
        $rows = $this->paymentQuery($request)->with(['unit.building', 'expense'])->get();

        $html = view('exports.payments', [
            'rows' => $rows,
            'title' => 'گزارش پرداخت‌ها'.$this->rangeLabel($request),
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
}
