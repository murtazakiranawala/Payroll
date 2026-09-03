<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\EmployeeSyncService;
use Illuminate\Console\Command;

class SyncEmployeesCommand extends Command
{
    protected $signature = 'payroll:sync-employees {school? : School code to sync, all active schools if omitted} {--full : Full load instead of incremental}';

    protected $description = 'Fetch employee master data from the AIIMS Central ERP (BRD FR-1)';

    public function handle(EmployeeSyncService $service): int
    {
        $schoolCode = $this->argument('school');
        $full = (bool) $this->option('full');

        $schools = $schoolCode
            ? School::where('code', $schoolCode)->get()
            : School::where('is_active', true)->get();

        if ($schools->isEmpty()) {
            $this->error('No matching active school(s) found.');

            return self::FAILURE;
        }

        foreach ($schools as $school) {
            $this->info("Syncing employees for [{$school->code}] ({$school->name})...");

            $log = $service->syncSchool($school, $full, 'scheduled');

            $this->line("  status={$log->status} fetched={$log->records_fetched} created={$log->records_created} updated={$log->records_updated} failed={$log->records_failed}");
        }

        return self::SUCCESS;
    }
}
