<?php

namespace App\Http\Controllers\Concerns;

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
 * Shared Excel/PDF stream writers for the list exports. Rows are arrays keyed by
 * the header label; a `$linkColumn` header renders that cell as a hyperlink.
 */
trait StreamsExports
{
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
        $lastColumn = end($letters);

        foreach ($headers as $i => $label) {
            $sheet->setCellValue($letters[$i].'1', $label);
        }
        $head = $sheet->getStyle("A1:{$lastColumn}1");
        $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('5B5BD6');
        $head->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = 2;
        foreach ($data as $row) {
            foreach (array_values($row) as $i => $val) {
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
            $sheet->getStyle("A1:{$lastColumn}".($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $filename = $slug.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function streamPdf(string $html, string $slug, string $format = 'A4')
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $format,
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
