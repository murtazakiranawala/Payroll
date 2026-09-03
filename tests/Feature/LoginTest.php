<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_login_id(): void
    {
        User::create([
            'name' => 'HR Manager', 'login_id' => 'HR', 'email' => 'hr@payroll.test',
            'password' => 'password', 'role' => 'hr', 'is_active' => true,
        ]);

        $response = $this->post('/login', ['login_id' => 'HR', 'password' => 'password']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::create([
            'name' => 'HR Manager', 'login_id' => 'HR', 'email' => 'hr@payroll.test',
            'password' => 'password', 'role' => 'hr', 'is_active' => true,
        ]);

        $response = $this->post('/login', ['login_id' => 'HR', 'password' => 'wrong']);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        User::create([
            'name' => 'HR Manager', 'login_id' => 'HR', 'email' => 'hr@payroll.test',
            'password' => 'password', 'role' => 'hr', 'is_active' => false,
        ]);

        $response = $this->post('/login', ['login_id' => 'HR', 'password' => 'password']);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest();
    }
}
