<?php

namespace App\Services;

use App\Contracts\EmployeeSyncProviderInterface;
use App\Models\Employee;
use App\Models\EmployeeSyncException;
use App\Models\EmployeeSyncLog;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * BRD FR-1: fetch employee master data from AIIMS, validate it, upsert into
 * the Payroll module, and log every run (and any records that fail
 * validation) so HR can correct the source data in AIIMS.
 */
class EmployeeSyncService
{
    public function __construct(private EmployeeSyncProviderInterface $provider)
    {
    }

    public function syncSchool(School $school, bool $full = false, string $runType = 'manual', ?int $triggeredBy = null): EmployeeSyncLog
    {
        $log = EmployeeSyncLog::create([
            'school_id' => $school->id,
            'run_type' => $runType,
            'sync_mode' => $full ? 'full' : 'incremental',
            'status' => 'running',
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
        ]);

        try {
            $lastSyncedAt = $school->employees()->max('last_synced_at');
            $since = $lastSyncedAt ? Carbon::parse($lastSyncedAt) : now()->subDay();

            $records = $full
                ? $this->provider->fetchFull($school)
                : $this->provider->fetchIncremental($school, $since);

            $created = 0;
            $updated = 0;
            $failed = 0;

            foreach ($records as $record) {
                $validated = $this->validateRecord($record);

                if ($validated->fails()) {
                    $failed++;
                    EmployeeSyncException::create([
                        'employee_sync_log_id' => $log->id,
                        'external_employee_code' => $record['external_employee_code'] ?? null,
                        'payload' => $record,
                        'error_message' => implode(' ', $validated->errors()->all()),
                    ]);

                    continue;
                }

                $data = $validated->validated();

                $employee = Employee::withTrashed()
                    ->where('school_id', $school->id)
                    ->where('external_employee_code', $data['external_employee_code'])
                    ->first();

                $wasNew = $employee === null;

                $employee ??= new Employee(['school_id' => $school->id]);
                $employee->fill($data + ['source' => 'aiims_sync', 'last_synced_at' => now()]);

                if ($employee->trashed()) {
                    $employee->restore();
                }

                $employee->save();

                $wasNew ? $created++ : $updated++;
            }

            $log->update([
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'records_fetched' => count($records),
                'records_created' => $created,
                'records_updated' => $updated,
                'records_failed' => $failed,
                'finished_at' => now(),
            ]);

            if ($failed > 0) {
                Log::warning("AIIMS sync for school [{$school->code}] completed with {$failed} record(s) failing validation.", [
                    'employee_sync_log_id' => $log->id,
                ]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            Log::error('AIIMS sync failed: '.$e->getMessage(), ['school_id' => $school->id]);
        }

        return $log->fresh();
    }

    private function validateRecord(array $record): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($record, [
            'external_employee_code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:teaching,non_teaching,administrative,support,other'],
            'date_of_joining' => ['nullable', 'date'],
            'date_of_exit' => ['nullable', 'date'],
            'employment_status' => ['nullable', 'in:active,on_leave,exited'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'bank_ifsc' => ['nullable', 'string', 'max:16'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'pan' => ['nullable', 'string', 'max:16'],
            'uan_number' => ['nullable', 'string', 'max:32'],
            'esi_number' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
