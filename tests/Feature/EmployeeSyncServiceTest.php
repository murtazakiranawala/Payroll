<?php

namespace Tests\Feature;

use App\Contracts\EmployeeSyncProviderInterface;
use App\Models\School;
use App\Services\EmployeeSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_records_are_upserted_and_invalid_ones_are_logged_as_exceptions(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);

        $fake = new class implements EmployeeSyncProviderInterface
        {
            public function fetchFull(School $school): array
            {
                return [
                    ['external_employee_code' => 'E1', 'name' => 'Valid Employee', 'employment_status' => 'active'],
                    ['external_employee_code' => 'E2', 'name' => ''], // fails validation: name required
                ];
            }

            public function fetchIncremental(School $school, \DateTimeInterface $since): array
            {
                return [];
            }
        };

        $service = new EmployeeSyncService($fake);
        $log = $service->syncSchool($school, full: true, runType: 'manual');

        $this->assertSame('completed_with_errors', $log->status);
        $this->assertSame(2, $log->records_fetched);
        $this->assertSame(1, $log->records_created);
        $this->assertSame(1, $log->records_failed);
        $this->assertSame(1, $log->exceptions()->count());

        $this->assertDatabaseHas('employees', ['external_employee_code' => 'E1', 'school_id' => $school->id]);
    }

    public function test_resyncing_the_same_code_updates_rather_than_duplicates(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);

        $fake = new class implements EmployeeSyncProviderInterface
        {
            public function fetchFull(School $school): array
            {
                return [['external_employee_code' => 'E1', 'name' => 'Updated Name', 'employment_status' => 'active']];
            }

            public function fetchIncremental(School $school, \DateTimeInterface $since): array
            {
                return $this->fetchFull($school);
            }
        };

        $service = new EmployeeSyncService($fake);
        $service->syncSchool($school, full: true, runType: 'manual');
        $service->syncSchool($school, full: true, runType: 'manual');

        $this->assertSame(1, \App\Models\Employee::where('school_id', $school->id)->count());
        $this->assertSame('Updated Name', \App\Models\Employee::first()->name);
    }
}
