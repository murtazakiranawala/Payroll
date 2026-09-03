<?php

namespace App\Contracts;

use App\Models\School;

/**
 * Source-side of BRD FR-1: fetching employee master data from the AIIMS
 * Central ERP. Implementations return an array of plain associative arrays
 * shaped like:
 *
 * [
 *   'external_employee_code' => string,
 *   'name' => string,
 *   'designation' => ?string,
 *   'department' => ?string,
 *   'category' => 'teaching'|'non_teaching'|'administrative'|'support'|'other',
 *   'date_of_joining' => ?string (Y-m-d),
 *   'date_of_exit' => ?string (Y-m-d),
 *   'employment_status' => 'active'|'on_leave'|'exited',
 *   'email' => ?string,
 *   'phone' => ?string,
 *   'bank_account_number' => ?string,
 *   'bank_ifsc' => ?string,
 *   'bank_name' => ?string,
 *   'pan' => ?string,
 *   'uan_number' => ?string,
 *   'esi_number' => ?string,
 * ]
 *
 * The exact field list is to be finalized jointly with the AIIMS team
 * (BRD §5) — this shape is the module's working assumption until then.
 */
interface EmployeeSyncProviderInterface
{
    /**
     * Full load of every employee record known to AIIMS for the given school.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchFull(School $school): array;

    /**
     * Delta sync: employees created/updated/exited since $since.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchIncremental(School $school, \DateTimeInterface $since): array;
}
