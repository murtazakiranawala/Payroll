# Payroll Module — Majma al Markazi

A School Employee Payroll module that (a) pulls employee master data from the **AIIMS Central ERP** and (b) posts finalized payroll journal entries into the org's in-house **Financial ERP**, per `BRD_Payroll.docx`. Built on Laravel (11.x or 12.x) / PHP 8.2+ / MySQL, per the commercial proposal's technology stack (PHP Laravel, MySQL, HTML/CSS/JS/jQuery, PDF/Excel reports).

## Status: verified working end-to-end

This repo was originally hand-written without a PHP toolchain available, then actually installed and run (PHP 8.2 + Composer via winget, Laravel 12.66 resolved by Composer since the 11.x line is now blocked by security advisories) and walked through the full flow in a browser: login → AIIMS sync → payroll computation → HR review → Finance approval → balanced journal voucher → posted to the mock Financial ERP → reconciliation (zero variance) → Salary Register / Bank Advice Excel exports. All 20 PHPUnit tests pass. A number of real bugs were found and fixed in the process (see "Bugs found and fixed" below) — nothing to redo on setup, but worth knowing about if you're extending the code.

A second round added: per-employee PF/ESI/PT/LWF applicability toggles (replacing the old date-ranged exemption model), per-school statutory rate overrides, a Salary Register export matching a reference Excel workbook column-for-column, an Excel bank payment file, manual per-cycle earnings/deductions (OT, bonus, arrears, wage overrides, attendance detail) with a shared edit modal, and Login ID (`HR`/`Finance`/`Superadmin`/`Management`/`Schooladmin`) replacing email as the sign-in identifier.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

For a quick local run with **zero database server setup**, point `.env` at SQLite instead of MySQL:

```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/project/database/database.sqlite
```
(create an empty `database/database.sqlite` file first). For a MySQL setup matching the proposal's production stack, create a database and set `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` instead — both work identically against this schema.

```bash
php artisan migrate --seed
php artisan serve
```

`migrate --seed` seeds the organization's real 50-school roster (`SchoolSeeder::SCHOOLS`), one user per role, the standard salary components, sample statutory rate slabs, and default GL account mappings. Every school is created clean — no fictional employees attached. To keep the full payroll cycle demoable out of the box without mixing fake staff into real institutions, exactly two schools (**JEMS Dholka** and **SHSS Khergone**) additionally get a full AIIMS mock sync (~10 employees each) plus a starter salary structure; every other school stays empty until it's actually synced from AIIMS (or has employees added manually) via the Employees screen.

Demo logins (**Login ID**, not email; password `password` for all): `Superadmin`, `HR`, `Finance`, `Management`, `Schooladmin`. Each user's `@payroll.test` email still exists on the record as a contact field, just not used to sign in.

Run the test suite:

```bash
php artisan test
```

## Demoing the full cycle

1. Log in as `HR`. **Employees** screen already has synced employees — open one and check the **Statutory Applicability** section / edit form for the PF/ESI/PT/LWF Applicable toggles.
2. **Payroll Cycles → New Cycle** to create a draft for the current month, then **Run Payroll**. Click the **Edit** icon on any row to open the manual-adjustments modal (LOP days, attendance detail, OT/bonus/arrears, PF/ESI wage overrides) — save it, then **Recompute Payroll** to fold it in.
3. **Submit for HR Review**, then **HR Approve → Finance**.
4. Log in as `Finance`: **Finance Approve**, then **Generate Journal Voucher** (now correctly balances even with manual earnings/deductions present), then **Post to Financial ERP**.
5. Generate the **Bank Advice** file (now an `.xlsx`) and the **Reconciliation** report from the same page.
6. **Reports** in the sidebar: **Salary Register** (`.xlsx`, matches the reference workbook's exact column layout), department-wise, PF/ESI/TDS/PT/LWF, and sync-log reports (CSV).
7. **Statutory Rates**: add a rate with a specific **School** selected (instead of "Default") to override the org-wide default just for that school — `StatutoryRateConfig::activeFor()` prefers the school-specific row when both exist and are in their effective-date window.

## Swapping the mock integrations for the real AIIMS / Financial ERP

Both integrations are behind interfaces (`App\Contracts\EmployeeSyncProviderInterface`, `App\Contracts\FinancialPostingProviderInterface`), bound in `App\Providers\AppServiceProvider` based on config drivers:

- `AIIMS_DRIVER=mock` (default) → `AiimsMockProvider` (fixture data). Set `AIIMS_DRIVER=http` plus `AIIMS_BASE_URL`/`AIIMS_API_KEY` once AIIMS confirms its API — `AiimsHttpProvider` is structurally ready but its endpoint path/payload shape are placeholders (BRD §5/§6.1 defer this to design).
- `FINANCIAL_ERP_DRIVER=mock` (default) → `FinancialErpMockProvider` (simulated posting). Same pattern via `FinancialErpHttpProvider` / `FINANCIAL_ERP_BASE_URL` / `FINANCIAL_ERP_API_KEY` (BRD §6.2).

## BRD requirement → code mapping

| BRD ref | Requirement | Where |
|---|---|---|
| FR-1.1–1.6 | AIIMS employee sync, full/incremental, validation, exceptions, audit log, manual re-sync | `EmployeeSyncService`, `AiimsMockProvider`/`AiimsHttpProvider`, `payroll:sync-employees` command, `EmployeeController@syncNow`, `employee_sync_logs`/`employee_sync_exceptions` |
| §5 | Employee master data fields | `employees` migration/model, `EmployeeSyncProviderInterface` docblock |
| FR-2.1 | Monthly salary computation | `PayrollComputationService` |
| FR-2.2 | Configurable, per-school PF/ESI/TDS/PT/LWF + per-employee applicability | `StatutoryComputationService`, `statutory_rate_configs` (nullable `school_id`), `employees.pf_applicable`/`esi_applicable`/`pt_applicable`/`lwf_applicable` |
| FR-2.3 | Reimbursement claims in payroll cycle | `ReimbursementClaimController`, consumed in `PayrollComputationService` |
| FR-2.4 | Full & Final settlement | `FnfSettlementService`, `fnf_settlements` |
| FR-2.5/2.7 | Approval workflow, correction before posting | `PayrollApprovalService` |
| FR-2.6 | Payslips, statutory reports, Salary Register | `PayslipService` (PDF), `SalaryRegisterExportService` (.xlsx, matches the reference workbook), `ReportController` (CSV for dept-wise/statutory/sync-log) |
| FR-3.1–3.6 | JV generation/posting/idempotency/reversal | `JournalVoucherService`, `journal_vouchers`/`journal_voucher_lines`, `FinancialPostingProviderInterface` |
| FR-3.7 | Bank payment Excel file | `BankAdviceFileService` (.xlsx via PhpSpreadsheet) |
| §9 (reporting) | Reconciliation, payroll register, dept-wise, statutory reports, sync log | `ReconciliationService`, `ReportController` |
| NFR: security | RBAC (`Role`, `EnsureRole`), encrypted PAN/bank fields, masked display, audit trail (`Auditable` trait) | `app/Support/Role.php`, `app/Concerns/Auditable.php`, `Employee` casts |

## Bugs found and fixed during the live run

- **Route ordering**: `payroll-cycles/create` and `employees/create` were registered *after* the `payroll-cycles/{cycle}` / `employees/{employee}` wildcard routes in `routes/modules.php`. Laravel matches routes in registration order, so `GET /employees/create` was being caught by the wildcard first, trying (and failing) to bind a model with route key `"create"`, producing a 404. Fixed by moving both static routes above their wildcard siblings — check for this pattern anywhere else a new static sub-route gets added under an existing resource prefix.
- **Missing `storage/framework/*` directories**: the hand-written repo never created `storage/framework/{cache/data,sessions,views,testing}` or `storage/logs` (normally scaffolded by `laravel/laravel`, and not something git tracks as empty directories). Without them Blade's view compiler throws `InvalidArgumentException: Please provide a valid cache path.` on the very first request. Fixed by creating the directories plus `.gitignore` placeholders in each so they survive a fresh clone.
- **Day-count rounding in `PayrollComputationService`**: `diffInDays()` between a period start (midnight) and `endOfMonth()` (23:59:59.999999) rounds a near-whole-month span *up* by a day (Carbon's default diff rounds rather than floors), producing `payable_days` one higher than `total_days` for a full month. Net pay was unaffected (the earnings ratio is separately capped at 1.0), but the displayed/stored day count was wrong. Fixed by diffing day-aligned (`startOfDay()`) copies of both dates and clamping `payable_days` to `total_days` as a safety net.
- **Blade comments can't nest**: `resources/views/components/money.blade.php`'s docblock comment (`{{-- ... --}}`) contained a *second* `{{-- ... --}}` inside its usage example. Blade matches the first `--}}` it finds, so the outer comment closed early and its real closing `--}}` rendered as literal text — every `<x-money>` value on every page in the app was printing a stray `--}} ₹ 0.00`. Fixed by rewriting the example without a nested comment. Not introduced by this session's changes, but broke live pages and was fixed since it was actively visible.
- **Unchecked checkboxes are absent from form submissions**: `EmployeeController`'s applicability-flag handling used `$request->boolean($flag, true)` — since an unchecked HTML checkbox simply isn't present in the POST body, the `true` default meant unchecking a box had no effect; it always read back as checked. Fixed by defaulting to `false` (native checkbox semantics), letting the *form* (not the controller) own the "checked by default for new employees" behavior.
- **Journal voucher went out of balance once manual earnings/deductions existed**: `JournalVoucherService`'s `salary_expense` debit line only summed `gross_earnings`, but `net_pay_payable` (credit) also includes `ot_amount`/`bonus_amount`/`arrears_amount`/`other_earnings_amount`. Any cycle with those fields set produced a voucher where credits exceeded debits by exactly that amount. Fixed by folding those fields into `salary_expense`, and added a new `other_deductions_payable` category (with its own GL mapping) since `other_deduction_amount` had no corresponding line on *either* side before. Covered by a new regression test (`test_voucher_balances_with_manual_earnings_and_deductions_present`).
- **`PhpOffice\PhpSpreadsheet\Worksheet::setCellValueByColumnAndRow()` doesn't exist in PhpSpreadsheet 5.x**: that method (present in older 1.x/2.x versions many code samples online still reference) was removed; `setCellValue()` now accepts an `[$columnIndex, $row]` array directly. Fixed in `BankAdviceFileService` (`SalaryRegisterExportService` was already written using the array-free `Coordinate::stringFromColumnIndex()` approach, so it was unaffected).

## Known simplifications (flagged, not silently assumed)

- **TDS** uses a single flat annual slab table applied to annualized gross — not a full Income Tax engine (no Section 80C/80D declarations, no old-vs-new-regime toggle). Treat `StatutoryComputationService::calculateTds` as a placeholder to replace once the org's actual TDS policy is confirmed.
- **Attendance/leave** is out of this BRD's scope; loss-of-pay is a manual `manual_lop_days` entry per payroll item (plus automatic pro-ration for mid-month joining/exit) rather than sourced from a dedicated attendance module.
- **Leave balance for F&F leave encashment** and **notice period** are HR-entered at settlement time (no leave ledger exists yet to source them from automatically).
- The BRD's "Stakeholders", "Assumptions and Constraints", "Open Items" and "Acceptance Criteria" sections had no extractable content in the source document — nothing here was built against them.
- **PT/LWF seed defaults are Gujarat-specific** (sourced from the reference workbook). Schools outside Gujarat need their own override added under Statutory Rates with that school selected — the mechanism is in place, the other states' actual rates aren't pre-loaded.
- **`other_deduction_amount` is a single lumped bucket** for loan/advance/canteen/insurance-type recoveries (matching the reference workbook's "Other Deductions/Recoveries" section, which the Salary Register export also collapses into one "Other Deduction" column) rather than tracking each sub-category separately.
- **Salary Register "Leave Encashment" column** is folded into "Other Earnings" in the export rather than broken out, to guarantee `Gross Pay − Total Deductions = Net Pay` holds exactly for every row even when an F&F settlement's notice-pay component (an *addition* in this app's model) doesn't map cleanly onto the workbook's "Notice / Excess Recovery" (a *deduction* in its model) — see `SalaryRegisterExportService`'s docblock.
- **Gratuity Provision** in the Salary Register export uses a fixed 4.81% accrual-rate constant (matching the reference workbook's value) rather than a configurable rate, since it's explicitly a display-only accounting estimate, not an employee deduction or a statutory rate type this app otherwise models.
