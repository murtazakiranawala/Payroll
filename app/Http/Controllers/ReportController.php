<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSyncLog;
use App\Models\PayrollCycle;
use App\Services\CsvExportService;
use App\Services\ReconciliationService;
use App\Services\SalaryRegisterExportService;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'cycles' => PayrollCycle::with('school')->orderByDesc('year')->orderByDesc('month')->get(),
        ]);
    }

    public function salaryRegister(PayrollCycle $cycle, SalaryRegisterExportService $exporter)
    {
        $spreadsheet = $exporter->build($cycle);
        $writer = new Xlsx($spreadsheet);
        $filename = "salary-register-{$cycle->school->code}-{$cycle->year}-{$cycle->month}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function departmentWise(PayrollCycle $cycle, CsvExportService $csv)
    {
        $rows = $cycle->items()->with('employee')->get()
            ->groupBy(fn ($i) => $i->employee->department ?? 'Unassigned')
            ->map(fn ($group, $dept) => [
                $dept, $group->count(), $group->sum('gross_earnings'), $group->sum('gross_deductions'), $group->sum('net_pay'),
            ])->values();

        return $this->csvResponse($csv->toCsv(
            ['Department', 'Employees', 'Gross Earnings', 'Deductions', 'Net Pay'],
            $rows->all()
        ), "department-wise-{$cycle->id}.csv");
    }

    public function statutory(PayrollCycle $cycle, string $type, CsvExportService $csv)
    {
        abort_unless(in_array($type, ['PF', 'ESI', 'TDS', 'PT', 'LWF']), 404);

        $columnMap = [
            'PF' => ['pf_employee', 'pf_employer'],
            'ESI' => ['esi_employee', 'esi_employer'],
            'TDS' => ['tds'],
            'PT' => ['pt'],
            'LWF' => ['lwf_employee', 'lwf_employer'],
        ];

        $columns = $columnMap[$type];

        $items = $cycle->items()->with('employee')->get()
            ->filter(fn ($i) => collect($columns)->sum(fn ($c) => (float) $i->$c) > 0);

        $rows = $items->map(function ($i) use ($columns) {
            $row = [$i->employee->external_employee_code, $i->employee->name, $i->employee->uan_number ?? $i->employee->esi_number];
            foreach ($columns as $c) {
                $row[] = $i->$c;
            }

            return $row;
        });

        $headers = array_merge(['Employee Code', 'Name', 'Statutory ID'], array_map('strtoupper', $columns));

        return $this->csvResponse($csv->toCsv($headers, $rows->all()), strtolower($type)."-report-{$cycle->id}.csv");
    }

    public function reconciliation(PayrollCycle $cycle)
    {
        return view('reports.reconciliation', ['cycle' => $cycle->load('reconciliationRecord', 'journalVoucher.lines', 'school')]);
    }

    public function generateReconciliation(PayrollCycle $cycle, ReconciliationService $service)
    {
        $service->generate($cycle);

        return redirect()->route('reports.reconciliation', $cycle)->with('status', 'Reconciliation report generated.');
    }

    public function syncLog()
    {
        return view('reports.sync-log', [
            'logs' => EmployeeSyncLog::with('school', 'triggeredBy', 'exceptions')->latest('started_at')->paginate(20),
        ]);
    }

    /**
     * Staff Grading & Compensation Policy §10: employees below their grade's
     * minimum need a catch-up plan; employees above the maximum must be
     * reported to the Idara. This lists everyone currently out of band.
     */
    public function compensationCompliance()
    {
        $employees = Employee::with(['school', 'staffGrade', 'salaryStructures' => fn ($q) => $q->where('is_active', true)])
            ->whereNotNull('staff_grade_id')
            ->where('employment_status', '!=', 'exited')
            ->orderBy('name')
            ->get()
            ->filter(fn ($e) => in_array($e->salaryComplianceStatus(), ['below_min', 'above_max'], true))
            ->values();

        return view('reports.compensation-compliance', ['employees' => $employees]);
    }

    private function csvResponse(string $csv, string $filename): Response
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
