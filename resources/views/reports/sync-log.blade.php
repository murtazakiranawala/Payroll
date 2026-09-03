@extends('layouts.app')

@section('title', 'AIIMS Sync Log')

@section('content')
<h4 class="mb-3"><i class="bi bi-arrow-repeat"></i> AIIMS Sync Log</h4>

<div class="card shadow-sm">
    <table class="table table-sm mb-0 data-table-nopage">
        <thead><tr><th>Started</th><th>School</th><th>Type</th><th>Mode</th><th>Status</th><th>Fetched</th><th>Created</th><th>Updated</th><th>Failed</th><th></th></tr></thead>
        <tbody>
        @foreach ($logs as $log)
            <tr>
                <td>{{ $log->started_at->format('d M Y H:i') }}</td>
                <td>{{ $log->school->name ?? '—' }}</td>
                <td>{{ $log->run_type }}</td>
                <td>{{ $log->sync_mode }}</td>
                <td><x-status-badge :status="$log->status" /></td>
                <td>{{ $log->records_fetched }}</td>
                <td>{{ $log->records_created }}</td>
                <td>{{ $log->records_updated }}</td>
                <td><span class="{{ $log->records_failed > 0 ? 'text-danger fw-semibold' : '' }}">{{ $log->records_failed }}</span></td>
                <td>
                    @if ($log->exceptions->isNotEmpty())
                        <button class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" data-bs-target="#exceptions-{{ $log->id }}"><i class="bi bi-exclamation-triangle text-danger"></i> {{ $log->exceptions->count() }} exception(s)</button>
                    @endif
                </td>
            </tr>
            @if ($log->exceptions->isNotEmpty())
                <tr class="collapse" id="exceptions-{{ $log->id }}">
                    <td colspan="10">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Employee Code</th><th>Error</th></tr></thead>
                            <tbody>
                            @foreach ($log->exceptions as $exception)
                                <tr><td>{{ $exception->external_employee_code }}</td><td class="small">{{ $exception->error_message }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
