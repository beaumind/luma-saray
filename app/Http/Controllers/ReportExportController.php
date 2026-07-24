<?php

namespace App\Http\Controllers;

use App\Support\DebtMatrix;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /** state → hex fill */
    private const STATE_FILL = [
        'paid' => 'C6EFCE',
        'partial' => 'FFEB9C',
        'unpaid' => 'F4CCCC',
        'neutral' => 'FFFFFF',
    ];

    private function resolve(Request $request): array
    {
        $buildingId = $request->integer('building') ?: null;
        $months = max(1, min(12, $request->integer('months', 3)));
        $matrix = DebtMatrix::build($buildingId, $months);

        $selected = array_filter(explode(',', (string) $request->query('cols', '')));
        $selected = array_merge(['number', 'resident'], $selected);

        $columns = array_values(array_filter(
            $matrix['columns'],
            fn ($c) => in_array($c['key'], $selected)
        ));

        return [$matrix, $columns];
    }

    private function cellText(array $row, array $col): string
    {
        $key = $col['key'];

        return match (true) {
            $key === 'number' => (string) $row['number'],
            $key === 'resident' => $row['resident'],
            $key === 'owner' => $row['owner'],
            $key === 'count' => (string) $row['count'],
            $key === 'monthly_charge' => number_format($row['monthly_charge']),
            $key === 'past_debt' => $row['past_debt'] ? number_format($row['past_debt']) : '0',
            $key === 'total_debt' => $row['total_debt'] ? number_format($row['total_debt']) : '0',
            $key === 'notes' => $row['notes'],
            str_starts_with($key, 'month_') => number_format($row['months'][$col['month']]['value']),
            default => '',
        };
    }

    private function cellFill(array $row, array $col): ?string
    {
        $key = $col['key'];
        if (str_starts_with($key, 'month_')) {
            return self::STATE_FILL[$row['months'][$col['month']]['state']] ?? null;
        }
        if ($key === 'past_debt') {
            return $row['past_debt'] > 0 ? 'FCE5CD' : 'D9EAD3';
        }
        if ($key === 'total_debt') {
            return $row['total_debt'] > 0 ? 'F4CCCC' : 'D9EAD3';
        }

        return null;
    }

    public function excel(Request $request): StreamedResponse
    {
        [$matrix, $columns] = $this->resolve($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('گزارش بدهی');

        $letters = [];
        foreach (range(1, count($columns)) as $i) {
            $letters[] = Coordinate::stringFromColumnIndex($i);
        }
        $lastColLetter = end($letters);

        // Header
        foreach ($columns as $i => $col) {
            $sheet->setCellValue($letters[$i].'1', $col['label']);
        }
        $head = $sheet->getStyle("A1:{$lastColLetter}1");
        $head->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('5B5BD6');
        $head->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Rows
        $rowIndex = 2;
        foreach ($matrix['rows'] as $row) {
            foreach ($columns as $i => $col) {
                $coord = $letters[$i].$rowIndex;
                $sheet->setCellValue($coord, $this->cellText($row, $col));
                $fill = $this->cellFill($row, $col);
                if ($fill) {
                    $sheet->getStyle($coord)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill);
                }
                $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $rowIndex++;
        }

        foreach ($letters as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(16);
        }
        $sheet->getStyle("A1:{$lastColLetter}".($rowIndex - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $filename = 'debt-report-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(Request $request)
    {
        [$matrix, $columns] = $this->resolve($request);

        $html = view('exports.debt-matrix', [
            'matrix' => $matrix,
            'columns' => $columns,
            'controller' => $this,
        ])->render();

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
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

        $filename = 'debt-report-'.now()->format('Ymd-His').'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Exposed for the PDF Blade view. */
    public function text(array $row, array $col): string
    {
        return $this->cellText($row, $col);
    }

    public function fillHex(array $row, array $col): ?string
    {
        return $this->cellFill($row, $col);
    }
}
