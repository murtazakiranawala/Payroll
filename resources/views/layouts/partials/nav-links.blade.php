{{--
    Shared nav items - included by both the desktop sidebar and the mobile
    offcanvas menu so there is one place to add/rename a section. Grouped
    into sections with a group label, matching the reference design.
    $pendingPayrollCount / $pendingReimbursementCount come from the
    view composer in AppServiceProvider::boot() - real counts, not decoration.
--}}
<div class="sidebar-group-label">Overview</div>
<ul class="nav nav-pills flex-column mb-2 gap-1">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>
</ul>

<div class="sidebar-group-label">Payroll</div>
<ul class="nav nav-pills flex-column mb-2 gap-1">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 justify-content-between {{ request()->routeIs('payroll-cycles.*') ? 'active' : '' }}" href="{{ route('payroll-cycles.index') }}">
            <span><i class="bi bi-calendar2-check"></i> Payroll Cycles</span>
            @if (($pendingPayrollCount ?? 0) > 0)
                <span class="badge rounded-pill text-bg-danger nav-badge">{{ $pendingPayrollCount }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
            <i class="bi bi-people"></i> Employees
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('salary-structures.*') ? 'active' : '' }}" href="{{ route('salary-structures.index') }}">
            <i class="bi bi-diagram-3"></i> Salary Structures
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('staff-grades.*') ? 'active' : '' }}" href="{{ route('staff-grades.index') }}">
            <i class="bi bi-bar-chart-steps"></i> Staff Grades
        </a>
    </li>
</ul>

<div class="sidebar-group-label">Finance</div>
<ul class="nav nav-pills flex-column mb-2 gap-1">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('statutory-rates.*') ? 'active' : '' }}" href="{{ route('statutory-rates.index') }}">
            <i class="bi bi-percent"></i> Statutory Rates
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('gl-mappings.*') ? 'active' : '' }}" href="{{ route('gl-mappings.index') }}">
            <i class="bi bi-link-45deg"></i> GL Mappings
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 justify-content-between {{ request()->routeIs('reimbursements.*') ? 'active' : '' }}" href="{{ route('reimbursements.index') }}">
            <span><i class="bi bi-receipt"></i> Reimbursements</span>
            @if (($pendingReimbursementCount ?? 0) > 0)
                <span class="badge rounded-pill text-bg-warning nav-badge">{{ $pendingReimbursementCount }}</span>
            @endif
        </a>
    </li>
</ul>

<div class="sidebar-group-label">Reports</div>
<ul class="nav nav-pills flex-column mb-2 gap-1">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
    </li>
</ul>

<div class="sidebar-group-label">Admin</div>
<ul class="nav nav-pills flex-column mb-auto gap-1">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('schools.*') ? 'active' : '' }}" href="{{ route('schools.index') }}">
            <i class="bi bi-building"></i> Schools
        </a>
    </li>
</ul>
