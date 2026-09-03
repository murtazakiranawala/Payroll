<?php

namespace Database\Seeders;

use App\Models\StaffGrade;
use Illuminate\Database\Seeder;

/**
 * Grades from the Staff Grading & Compensation Policy, Annexures A-1
 * (teaching), A-2 (administrative/management) and B-1 (basic pay bands +
 * yearly increment). Management grades (M1-M3) have no band yet - the
 * policy marks their pay levels "TBA".
 */
class StaffGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            // code, description, applicable_to, staff_type, min, max, yearly_increment
            ['T1-A', 'Teaching Assistant', 'Pre-Primary', 'teaching', 5600, 8600, 300],
            ['T1-B', 'Teaching Assistant', 'Primary', 'teaching', 5800, 9800, 400],
            ['T2-A', 'Junior Teacher', 'Pre-Primary', 'teaching', 7000, 12000, 500],
            ['T2-B', 'Junior Teacher', 'Primary', 'teaching', 7500, 12500, 500],
            ['T2-C', 'Junior Teacher', 'Middle', 'teaching', 8000, 13000, 500],
            ['T3-A', 'Teacher', 'Pre-Primary', 'teaching', 8200, 14200, 600],
            ['T3-B', 'Teacher', 'Primary', 'teaching', 8200, 14200, 600],
            ['T3-C', 'Teacher', 'Section Head - Pre-Primary', 'teaching', 9400, 16400, 700],
            ['T3-D', 'Teacher', 'Middle', 'teaching', 9400, 16400, 700],
            ['T3-E', 'Teacher', 'Secondary', 'teaching', 9400, 16400, 700],
            ['T3-F', 'Teacher', 'Higher Secondary', 'teaching', 10600, 18600, 800],
            ['T4-A', 'Senior Teacher', 'Primary', 'teaching', 11200, 19200, 800],
            ['T4-B', 'Senior Teacher', 'Middle', 'teaching', 11200, 19200, 800],
            ['T4-C', 'Senior Teacher', 'Section Head - Primary', 'teaching', 11800, 20800, 900],
            ['T4-D', 'Senior Teacher', 'Secondary', 'teaching', 11800, 20800, 900],
            ['T4-E', 'Senior Teacher', 'Higher Secondary', 'teaching', 12000, 22000, 1000],
            ['T5-A', 'Section / Department Head', 'Middle', 'teaching', 14000, 24000, 1000],
            ['T5-B', 'Section / Department Head', 'Secondary', 'teaching', 14000, 24000, 1000],
            ['T5-C', 'Section / Department Head', 'Higher Secondary', 'teaching', 14000, 24000, 1000],

            ['A1', 'Operator / Assistant', 'Office Staff', 'administrative', 5600, 8600, 300],
            ['A2', 'Sr. Operator / Sr. Assistant', 'Office Staff', 'administrative', 7000, 12000, 500],
            ['A3', 'Executive', 'Office Staff', 'administrative', 9000, 15000, 600],
            ['A4', 'Assistant Manager', 'Office Staff', 'administrative', 10000, 17000, 700],
            ['A5', 'Manager', 'Office Staff', 'administrative', 12000, 21000, 900],

            ['M1', 'Headmaster / Headmistress', 'Management', 'management', null, null, null],
            ['M2', 'Vice-Principal', 'Management', 'management', null, null, null],
            ['M3', 'Principal', 'Management', 'management', null, null, null],
        ];

        foreach ($grades as $i => [$code, $description, $applicableTo, $staffType, $min, $max, $increment]) {
            StaffGrade::updateOrCreate(['code' => $code], [
                'description' => $description,
                'applicable_to' => $applicableTo,
                'staff_type' => $staffType,
                'min_basic' => $min,
                'max_basic' => $max,
                'yearly_increment' => $increment,
                'sort_order' => $i,
            ]);
        }
    }
}
