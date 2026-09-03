<?php

namespace App\Concerns;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared letterhead helper for the Excel exports (Salary Register, Bank
 * Payment Advice) - floats the org logo over the given cell without
 * disturbing cell values, so it coexists with the merged title row.
 */
trait AddsExcelLogo
{
    private function addLogo(Worksheet $sheet, string $cell = 'A1', int $height = 42): void
    {
        $path = public_path('images/logo.png');

        if (! file_exists($path)) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath($path);
        $drawing->setHeight($height);
        $drawing->setCoordinates($cell);
        $drawing->setOffsetX(4);
        $drawing->setOffsetY(4);
        $drawing->setWorksheet($sheet);
    }
}
