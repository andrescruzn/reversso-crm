<?php

namespace Tests\Feature;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $conductorRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrador', 'description' => 'Admin']);
        $this->conductorRole = Role::create(['name' => 'conductor', 'display_name' => 'Conductor', 'description' => 'Driver']);
    }

    private function makeUser(string $roleName): User
    {
        $user = User::create([
            'name'      => "User {$roleName}",
            'email'     => "{$roleName}@reversso.com",
            'password'  => 'password123',
            'is_active' => true,
        ]);
        $user->assignRole(Role::where('name', $roleName)->first());
        return $user;
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('users.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('attendance.report'))->assertStatus(200);
    }

    public function test_conductor_cannot_access_admin_routes(): void
    {
        $conductor = $this->makeUser('conductor');

        $this->actingAs($conductor)->get(route('users.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($conductor)->get(route('attendance.report'))->assertRedirect(route('dashboard'));
    }

    public function test_middleware_checks_by_name_and_display_name(): void
    {
        $admin = $this->makeUser('admin');

        // hasRole works with both name and display_name
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasRole('Administrador'));

        // hasAnyRole works too
        $this->assertTrue($admin->hasAnyRole(['admin']));
        $this->assertTrue($admin->hasAnyRole(['Administrador']));
        $this->assertFalse($admin->hasAnyRole(['conductor']));
    }

    public function test_conductor_can_access_conductor_routes(): void
    {
        $conductor = $this->makeUser('conductor');

        // Conductor can access logistics history
        $this->actingAs($conductor)->get(route('logistics.history'))->assertStatus(200);
    }

    public function test_admin_cannot_access_conductor_only_routes(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('logistics.history'))->assertRedirect(route('dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }
}
