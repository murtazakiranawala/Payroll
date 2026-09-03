<?php

namespace App\Services;

use App\Concerns\AddsExcelLogo;
use App\Models\BankAdviceFile;
use App\Models\PayrollCycle;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * BRD FR-3.7: bank payment file for net salary disbursement, generated once
 * a payroll cycle has been approved (and ideally posted). Emits a generic
 * .xlsx (Sr No, employee/bank details, net payable amount) - the exact
 * layout a specific bank's bulk-upload template requires can be swapped in
 * here once the disbursing bank is confirmed.
 */
class BankAdviceFileService
{
    use AddsExcelLogo;

    public function generate(PayrollCycle $cycle, ?User $user = null): BankAdviceFile
    {
        if (! in_array($cycle->status, ['approved', 'posted'], true)) {
            throw new RuntimeException('Bank advice can only be generated for an approved or posted payroll cycle.');
        }

        $cycle->loadMissing('school');
        $items = $cycle->items()->with('employee')->where('net_pay', '>', 0)->get();

        $spreadsheet = $this->buildSpreadsheet($cycle, $items);
        $writer = new Xlsx($spreadsheet);

        $path = "bank-advices/cycle-{$cycle->id}-{$cycle->year}-{$cycle->month}.xlsx";
        $fullPath = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory('bank-advices');
        $writer->save($fullPath);

        return BankAdviceFile::updateOrCreate(
            ['payroll_cycle_id' => $cycle->id],
            [
                'file_path' => $path,
                'total_amount' => $items->sum('net_pay'),
                'record_count' => $items->count(),
                'generated_by' => $user?->id,
                'generated_at' => now(),
            ]
        );
    }

    private function buildSpreadsheet(PayrollCycle $cycle, \Illuminate\Support\Collection $items): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank Payment Advice');

        $sheet->setCellValue('A1', "Bank Payment Advice - {$cycle->school->name} - {$cycle->label()}");
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(36);
        $this->addLogo($sheet);

        $headers = ['Sr No', 'Employee Code', 'Employee Name', 'Department', 'Bank Name', 'Bank Account Number', 'IFSC', 'Net Payable Amount', 'Remarks'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 3], $header);
        }
        $sheet->getStyle('A3:I3')->getFont()->setBold(true);
        $sheet->getStyle('A3:I3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');

        $row = 4;

        foreach ($items as $i => $item) {
            $sheet->setCellValue([1, $row], $i + 1);
            $sheet->setCellValue([2, $row], $item->employee->external_employee_code);
            $sheet->setCellValue([3, $row], $item->employee->name);
            $sheet->setCellValue([4, $row], $item->employee->department);
            $sheet->setCellValue([5, $row], $item->employee->bank_name);
            $sheet->setCellValue([6, $row], $item->employee->bank_account_number);
            $sheet->setCellValue([7, $row], $item->employee->bank_ifsc);
            $sheet->setCellValue([8, $row], (float) $item->net_pay);
            $sheet->setCellValue([9, $row], $item->is_fnf ? 'Full & Final settlement' : '');
            $row++;
        }

        $sheet->setCellValue([7, $row], 'Total');
        $sheet->setCellValue([8, $row], (float) $items->sum('net_pay'));
        $sheet->getStyle("A{$row}:I{$row}")->getFont()->setBold(true);

        $sheet->getStyle("H4:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range(1, 9) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
