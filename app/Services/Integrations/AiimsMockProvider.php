<?php

namespace App\Services\Integrations;

use App\Contracts\EmployeeSyncProviderInterface;
use App\Models\School;
use Carbon\Carbon;

/**
 * Deterministic fixture data standing in for the real AIIMS Central ERP API
 * until its contract is confirmed (see App\Services\Integrations\AiimsHttpProvider
 * for the real-integration stub). Generates a stable roster per school so
 * repeated syncs are idempotent, plus a small "delta" for incremental runs.
 */
class AiimsMockProvider implements EmployeeSyncProviderInterface
{
    private const ROSTER = [
        ['name' => 'Ayesha Merchant', 'designation' => 'Senior Teacher', 'department' => 'Academics', 'category' => 'teaching'],
        ['name' => 'Zainab Kagzi', 'designation' => 'Teacher', 'department' => 'Academics', 'category' => 'teaching'],
        ['name' => 'Fatima Rangwala', 'designation' => 'Teacher', 'department' => 'Academics', 'category' => 'teaching'],
        ['name' => 'Husain Bharmal', 'designation' => 'Lab Assistant', 'department' => 'Science', 'category' => 'non_teaching'],
        ['name' => 'Yusuf Diwan', 'designation' => 'Accountant', 'department' => 'Accounts', 'category' => 'administrative'],
        ['name' => 'Mariam Saifee', 'designation' => 'HR Executive', 'department' => 'Administration', 'category' => 'administrative'],
        ['name' => 'Idris Vohra', 'designation' => 'Office Assistant', 'department' => 'Administration', 'category' => 'non_teaching'],
        ['name' => 'Ruqaiya Shakir', 'designation' => 'Librarian', 'department' => 'Library', 'category' => 'non_teaching'],
        ['name' => 'Taha Najmi', 'designation' => 'Security Supervisor', 'department' => 'Facilities', 'category' => 'support'],
        ['name' => 'Sakina Bootwala', 'designation' => 'Housekeeping Lead', 'department' => 'Facilities', 'category' => 'support'],
    ];

    public function fetchFull(School $school): array
    {
        return $this->roster($school);
    }

    public function fetchIncremental(School $school, \DateTimeInterface $since): array
    {
        // Simulate a delta: the first two employees have a "recent update" from AIIMS.
        return array_slice($this->roster($school), 0, 2);
    }

    private function roster(School $school): array
    {
        $joinDate = Carbon::parse('2022-06-01')->addDays($school->id ?? 0);

        return array_map(function (array $person, int $index) use ($school, $joinDate) {
            $code = sprintf('AIIMS-%03d-%03d', $school->id ?? 0, $index + 1);
            $bankSeed = str_pad((string) (($school->id ?? 0) * 1000 + $index + 1), 6, '0', STR_PAD_LEFT);

            return [
                'external_employee_code' => $code,
                'name' => $person['name'],
                'designation' => $person['designation'],
                'department' => $person['department'],
                'category' => $person['category'],
                'date_of_joining' => $joinDate->copy()->addMonths($index)->toDateString(),
                'date_of_exit' => null,
                'employment_status' => 'active',
                'email' => strtolower(str_replace(' ', '.', $person['name'])).'@'.($school->code ?? 'school').'.example.org',
                'phone' => '9000'.$bankSeed,
                'bank_account_number' => '5000123400'.$bankSeed,
                'bank_ifsc' => 'HDFC0001234',
                'bank_name' => 'HDFC Bank',
                'pan' => 'ABCDE'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT).'F',
                'uan_number' => '10020030'.$bankSeed,
                'esi_number' => null,
            ];
        }, self::ROSTER, array_keys(self::ROSTER));
    }
}
