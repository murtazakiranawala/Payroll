<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSyncLog;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\School;
use Illuminate\Http\Request;

/**
 * Management dashboard. Every figure here is derived from real records -
 * no fabricated metrics (e.g. we don't show an "on-time disbursement rate"
 * since there's no due-date concept to measure against; average approval
 * turnaround and sync health are shown instead, since those are real).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $now = now();
        $prevMonth = $now->copy()->subMonthNoOverflow();

        // School filter: empty selection means "all schools" (unchanged
        // default behaviour). Every query below that touches a school-owned
        // table is scoped through this same array.
        $schoolIds = array_filter(array_map('intval', (array) $request->input('school_ids', [])));
        $scoped = fn ($query, $column = 'school_id') => $schoolIds ? $query->whereIn($column, $schoolIds) : $query;

        $totalEmployees = $scoped(Employee::query())->count();
        $teachingCount = $scoped(Employee::where('category', 'teaching'))->count();
        $nonTeachingCount = $scoped(Employee::whereIn('category', ['non_teaching', 'administrative', 'support', 'other']))->count();
        $newJoinersThisMonth = $scoped(Employee::whereBetween('date_of_joining', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]))->count();

        $currentCycles = $scoped(PayrollCycle::with('school')->where('month', $now->month)->where('year', $now->year))->get();
        $currentItems = PayrollItem::whereIn('payroll_cycle_id', $currentCycles->pluck('id'))->with('employee')->get();

        $totalEarnings = (float) $currentItems->sum('gross_earnings');
        $totalDeductions = (float) $currentItems->sum('gross_deductions');
        $netPayroll = (float) $currentItems->sum('net_pay');
        $employeesProcessed = $currentItems->pluck('employee_id')->unique()->count();
        $currentPendingCount = $currentCycles->whereIn('status', ['hr_review', 'finance_review'])->count();

        $prevCycleIds = $scoped(PayrollCycle::where('month', $prevMonth->month)->where('year', $prevMonth->year))->pluck('id');
        $prevGross = (float) PayrollItem::whereIn('payroll_cycle_id', $prevCycleIds)->sum('gross_earnings');
        $prevNet = (float) PayrollItem::whereIn('payroll_cycle_id', $prevCycleIds)->sum('net_pay');

        $grossTrendPct = $prevGross > 0 ? round((($totalEarnings - $prevGross) / $prevGross) * 100, 1) : null;
        $netTrendPct = $prevNet > 0 ? round((($netPayroll - $prevNet) / $prevNet) * 100, 1) : null;
        // Null (not zero-baselined) when there's no prior month to compare against -
        // "+100% variance vs a month with no payroll" would be a meaningless number.
        $costVariance = $prevGross > 0 ? $totalEarnings - $prevGross : null;

        $pendingCycles = $scoped(PayrollCycle::with('school')->whereIn('status', ['hr_review', 'finance_review']))->get();
        $pendingCaption = $pendingCycles->isNotEmpty()
            ? $pendingCycles->map(fn ($c) => $c->school->code.' '.$c->label())->implode(', ').' awaiting sign-off'
            : 'Nothing blocked right now';

        $lastSync = $scoped(EmployeeSyncLog::query())->latest('started_at')->first();

        // Average approval turnaround: draft creation -> finance approval, last 10 finalized cycles.
        $finalizedCycles = $scoped(PayrollCycle::whereNotNull('finance_approved_at'))->latest('finance_approved_at')->take(10)->get(['created_at', 'finance_approved_at']);
        $avgTurnaroundMinutes = $finalizedCycles->isNotEmpty()
            ? (int) round($finalizedCycles->avg(fn ($c) => $c->created_at->diffInMinutes($c->finance_approved_at)))
            : null;

        // 12-month dual-series (gross vs net) trend.
        $trend = ['labels' => [], 'gross' => [], 'net' => []];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonthsNoOverflow($i);
            $ids = $scoped(PayrollCycle::where('month', $month->month)->where('year', $month->year))->pluck('id');
            $trend['labels'][] = $month->format('M Y');
            $trend['gross'][] = round((float) PayrollItem::whereIn('payroll_cycle_id', $ids)->sum('gross_earnings'), 2);
            $trend['net'][] = round((float) PayrollItem::whereIn('payroll_cycle_id', $ids)->sum('net_pay'), 2);
        }
        $ytdGross = array_sum($trend['gross']);
        $ytdNet = array_sum($trend['net']);
        $avgHeadcount = $currentItems->isNotEmpty() ? $employeesProcessed : $totalEmployees;

        // Department-wise cost breakdown, current month.
        $deptBreakdown = $currentItems
            ->groupBy(fn ($item) => $item->employee->department ?: 'Unassigned')
            ->map(fn ($rows, $dept) => ['department' => $dept, 'total' => round((float) $rows->sum('net_pay'), 2)])
            ->sortByDesc('total')
            ->take(7)
            ->values();
        $deptTotalCost = $deptBreakdown->sum('total');
        $topDeptShare = $deptTotalCost > 0 && $deptBreakdown->isNotEmpty()
            ? round($deptBreakdown->first()['total'] / $deptTotalCost * 100, 1)
            : null;

        $recentRuns = $scoped(PayrollCycle::with(['school', 'creator'])
            ->withCount('items')
            ->withSum('items as gross_sum', 'gross_earnings')
            ->withSum('items as net_sum', 'net_pay'))
            ->orderByDesc('year')->orderByDesc('month')
            ->take(8)
            ->get();

        // Audit log entries aren't tied to a school column directly (they're
        // polymorphic across several model types), so this feed stays
        // org-wide regardless of the school filter.
        $recentActivity = AuditLog::with('user')->latest('created_at')->take(8)->get();

        $schools = School::orderBy('name')->get(['id', 'name', 'code']);

        return view('dashboard', compact(
            'totalEmployees', 'teachingCount', 'nonTeachingCount', 'newJoinersThisMonth',
            'totalEarnings', 'totalDeductions', 'netPayroll', 'employeesProcessed', 'currentPendingCount',
            'grossTrendPct', 'netTrendPct', 'costVariance',
            'pendingCycles', 'pendingCaption', 'lastSync', 'avgTurnaroundMinutes',
            'trend', 'ytdGross', 'ytdNet', 'avgHeadcount',
            'deptBreakdown', 'topDeptShare', 'recentRuns', 'recentActivity',
            'schools', 'schoolIds'
        ));
    }
}
