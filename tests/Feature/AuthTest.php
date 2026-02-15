<?php

namespace Tests\Feature;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin', 'display_name' => 'Administrador', 'description' => 'Admin']);
        Role::create(['name' => 'conductor', 'display_name' => 'Conductor', 'description' => 'Driver']);
    }

    private function createUser(string $roleName = 'conductor', bool $active = true): User
    {
        $user = User::create([
            'name'      => 'Test User',
            'email'     => 'test@reversso.com',
            'password'  => 'password123',
            'is_active' => $active,
        ]);

        $role = Role::where('name', $roleName)->first();
        $user->assignRole($role);

        return $user;
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->seedRoles();
        $this->createUser();

        $response = $this->post('/login', [
            'email'    => 'test@reversso.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $this->seedRoles();
        $this->createUser();

        $response = $this->post('/login', [
            'email'    => 'test@reversso.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->seedRoles();
        $this->createUser(active: false);

        $response = $this->post('/login', [
            'email'    => 'test@reversso.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $this->seedRoles();
        $user = $this->createUser();

        $this->actingAs($user);
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $this->seedRoles();
        $user = $this->createUser();

        $this->actingAs($user);
        $response = $this->get('/login');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
    }
}
