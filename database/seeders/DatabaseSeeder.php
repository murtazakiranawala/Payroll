<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SchoolSeeder::class,
            LocationTierSeeder::class,
            UserSeeder::class,
            SalaryComponentSeeder::class,
            StaffGradeSeeder::class,
            StatutoryRateConfigSeeder::class,
            GlAccountMappingSeeder::class,
            DemoEmployeeSeeder::class,
        ]);
    }
}
