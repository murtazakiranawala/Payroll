<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Role;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'login_id' => 'Superadmin', 'email' => 'superadmin@payroll.test', 'role' => Role::SUPER_ADMIN],
            ['name' => 'HR Manager', 'login_id' => 'HR', 'email' => 'hr@payroll.test', 'role' => Role::HR],
            ['name' => 'Finance Manager', 'login_id' => 'Finance', 'email' => 'finance@payroll.test', 'role' => Role::FINANCE],
            ['name' => 'Management Viewer', 'login_id' => 'Management', 'email' => 'management@payroll.test', 'role' => Role::MANAGEMENT],
            ['name' => 'School Admin', 'login_id' => 'Schooladmin', 'email' => 'schooladmin@payroll.test', 'role' => Role::SCHOOL_ADMIN],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['login_id' => $user['login_id']], $user + [
                'password' => 'password',
                'is_active' => true,
            ]);
        }
    }
}
