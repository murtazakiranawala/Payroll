<?php

use App\Http\Controllers\BankAdviceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GlAccountMappingController;
use App\Http\Controllers\IncrementController;
use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\PayrollCycleController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ReimbursementClaimController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StaffGradeController;
use App\Http\Controllers\StatutoryRateConfigController;
use App\Support\Role;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // --- Schools & GL mappings (Super Admin / Finance) ---
    Route::middleware('role:'.Role::FINANCE)->group(function () {
        Route::resource('schools', SchoolController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::get('gl-mappings', [GlAccountMappingController::class, 'index'])->name('gl-mappings.index');
        Route::post('gl-mappings', [GlAccountMappingController::class, 'store'])->name('gl-mappings.store');
        Route::delete('gl-mappings/{glAccountMapping}', [GlAccountMappingController::class, 'destroy'])->name('gl-mappings.destroy');
    });

    // --- Employees (HR maintains, everyone with access can view) ---
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    // Static "create" must be registered before the "{employee}" wildcard below,
    // or Laravel matches the wildcard first and 404s trying to bind a model
    // with route key "create".
    Route::get('employees/create', [EmployeeController::class, 'create'])
        ->middleware('role:'.Role::HR)->name('employees.create');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    Route::middleware('role:'.Role::HR)->group(function () {
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('employees/sync', [EmployeeController::class, 'syncNow'])->name('employees.sync');

        Route::get('employees/{employee}/salary-structure/create', [SalaryStructureController::class, 'create'])->name('salary-structures.create');
        Route::post('employees/{employee}/salary-structure', [SalaryStructureController::class, 'store'])->name('salary-structures.store');

        // Static "bulk" must be registered before the "{employee}" wildcard below.
        Route::get('increments/bulk', [IncrementController::class, 'bulkForm'])->name('increments.bulk');
        Route::post('increments/bulk', [IncrementController::class, 'bulkStore'])->name('increments.bulk-store');
        Route::get('employees/{employee}/increment', [IncrementController::class, 'create'])->name('increments.create');
        Route::post('employees/{employee}/increment', [IncrementController::class, 'store'])->name('increments.store');
    });
    Route::get('salary-structures', [SalaryStructureController::class, 'index'])->name('salary-structures.index');

    // --- Staff grades (Staff Grading & Compensation Policy bands - HR maintains, everyone can view) ---
    Route::get('staff-grades', [StaffGradeController::class, 'index'])->name('staff-grades.index');
    Route::middleware('role:'.Role::HR)->group(function () {
        Route::get('staff-grades/create', [StaffGradeController::class, 'create'])->name('staff-grades.create');
        Route::post('staff-grades', [StaffGradeController::class, 'store'])->name('staff-grades.store');
        Route::get('staff-grades/{staffGrade}/edit', [StaffGradeController::class, 'edit'])->name('staff-grades.edit');
        Route::put('staff-grades/{staffGrade}', [StaffGradeController::class, 'update'])->name('staff-grades.update');
    });

    // --- Statutory rate configuration (Super Admin / Finance) ---
    Route::get('statutory-rates', [StatutoryRateConfigController::class, 'index'])->name('statutory-rates.index');
    Route::middleware('role:'.Role::FINANCE)->group(function () {
        Route::get('statutory-rates/create', [StatutoryRateConfigController::class, 'create'])->name('statutory-rates.create');
        Route::post('statutory-rates', [StatutoryRateConfigController::class, 'store'])->name('statutory-rates.store');
        Route::get('statutory-rates/{statutoryRateConfig}/edit', [StatutoryRateConfigController::class, 'edit'])->name('statutory-rates.edit');
        Route::put('statutory-rates/{statutoryRateConfig}', [StatutoryRateConfigController::class, 'update'])->name('statutory-rates.update');
    });

    // --- Reimbursement claims ---
    Route::get('reimbursements', [ReimbursementClaimController::class, 'index'])->name('reimbursements.index');
    Route::get('reimbursements/create', [ReimbursementClaimController::class, 'create'])->name('reimbursements.create');
    Route::post('reimbursements', [ReimbursementClaimController::class, 'store'])->name('reimbursements.store');
    Route::middleware('role:'.Role::HR.','.Role::FINANCE)->group(function () {
        Route::post('reimbursements/{reimbursementClaim}/approve', [ReimbursementClaimController::class, 'approve'])->name('reimbursements.approve');
        Route::post('reimbursements/{reimbursementClaim}/reject', [ReimbursementClaimController::class, 'reject'])->name('reimbursements.reject');
    });

    // --- Payroll cycles: the core workflow ---
    Route::get('payroll-cycles', [PayrollCycleController::class, 'index'])->name('payroll-cycles.index');
    // Same ordering requirement as employees/create above.
    Route::get('payroll-cycles/create', [PayrollCycleController::class, 'create'])
        ->middleware('role:'.Role::HR)->name('payroll-cycles.create');
    Route::get('payroll-cycles/{cycle}', [PayrollCycleController::class, 'show'])->name('payroll-cycles.show');

    Route::middleware('role:'.Role::HR)->group(function () {
        Route::post('payroll-cycles', [PayrollCycleController::class, 'store'])->name('payroll-cycles.store');
        Route::post('payroll-cycles/{cycle}/compute', [PayrollCycleController::class, 'compute'])->name('payroll-cycles.compute');
        Route::put('payroll-cycles/{cycle}/items/{item}', [PayrollCycleController::class, 'updateItem'])->name('payroll-cycles.items.update');
        Route::post('payroll-cycles/{cycle}/fnf/{fnfSettlement}', [PayrollCycleController::class, 'updateFnf'])->name('payroll-cycles.fnf.update');
        Route::post('payroll-cycles/{cycle}/submit-hr-review', [PayrollCycleController::class, 'submitForHrReview'])->name('payroll-cycles.submit-hr-review');
        Route::post('payroll-cycles/{cycle}/approve-hr', [PayrollCycleController::class, 'approveByHr'])->name('payroll-cycles.approve-hr');
        Route::post('payroll-cycles/{cycle}/reject', [PayrollCycleController::class, 'reject'])->name('payroll-cycles.reject');
    });

    Route::middleware('role:'.Role::FINANCE)->group(function () {
        Route::post('payroll-cycles/{cycle}/approve-finance', [PayrollCycleController::class, 'approveByFinance'])->name('payroll-cycles.approve-finance');
        Route::post('payroll-cycles/{cycle}/reopen', [PayrollCycleController::class, 'reopen'])->name('payroll-cycles.reopen');

        Route::post('payroll-cycles/{cycle}/voucher', [JournalVoucherController::class, 'build'])->name('journal-vouchers.build');
        Route::post('journal-vouchers/{journalVoucher}/post', [JournalVoucherController::class, 'post'])->name('journal-vouchers.post');
        Route::post('journal-vouchers/{journalVoucher}/reverse', [JournalVoucherController::class, 'reverse'])->name('journal-vouchers.reverse');

        Route::post('payroll-cycles/{cycle}/bank-advice', [BankAdviceController::class, 'generate'])->name('bank-advice.generate');
        Route::get('bank-advice/{bankAdviceFile}/download', [BankAdviceController::class, 'download'])->name('bank-advice.download');

        Route::post('payroll-cycles/{cycle}/reconciliation', [ReportController::class, 'generateReconciliation'])->name('reports.reconciliation.generate');
    });

    Route::get('payroll-cycles/{cycle}/items/{item}/payslip', [PayslipController::class, 'show'])->name('payslips.show');

    // --- Reports ---
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/salary-register/{cycle}', [ReportController::class, 'salaryRegister'])->name('reports.salary-register');
    Route::get('reports/department-wise/{cycle}', [ReportController::class, 'departmentWise'])->name('reports.department-wise');
    Route::get('reports/statutory/{cycle}/{type}', [ReportController::class, 'statutory'])->name('reports.statutory');
    Route::get('reports/reconciliation/{cycle}', [ReportController::class, 'reconciliation'])->name('reports.reconciliation');
    Route::get('reports/sync-log', [ReportController::class, 'syncLog'])->name('reports.sync-log');
    Route::get('reports/compensation-compliance', [ReportController::class, 'compensationCompliance'])->name('reports.compensation-compliance');
});
