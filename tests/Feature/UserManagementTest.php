<?php

namespace Tests\Feature;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $conductorRole;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrador', 'description' => 'Admin']);
        $this->conductorRole = Role::create(['name' => 'conductor', 'display_name' => 'Conductor', 'description' => 'Driver']);

        $this->admin = User::create([
            'name'      => 'Admin',
            'email'     => 'admin@reversso.com',
            'password'  => 'password123',
            'is_active' => true,
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_admin_can_see_users_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('Admin');
    }

    public function test_admin_can_see_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name'     => 'Nuevo Conductor',
            'email'    => 'conductor@reversso.com',
            'password' => 'password123',
            'role'     => 'conductor',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'conductor@reversso.com']);

        $newUser = User::where('email', 'conductor@reversso.com')->first();
        $this->assertTrue($newUser->hasRole('Conductor'));
        $this->assertTrue((bool) $newUser->is_active);
    }

    public function test_admin_can_edit_user(): void
    {
        $user = User::create([
            'name'      => 'Old Name',
            'email'     => 'old@reversso.com',
            'password'  => 'password123',
            'is_active' => true,
        ]);
        $user->assignRole($this->conductorRole);

        $response = $this->actingAs($this->admin)->put(route('users.update', $user->id), [
            'name'  => 'New Name',
            'email' => 'new@reversso.com',
            'role'  => 'conductor',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'New Name',
            'email' => 'new@reversso.com',
        ]);
    }

    public function test_admin_can_change_user_password(): void
    {
        $user = User::create([
            'name'      => 'Test',
            'email'     => 'test@reversso.com',
            'password'  => 'oldpassword1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('users.password.update', $user->id), [
            'password'              => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $response->assertRedirect(route('users.index'));

        // Verify new password works
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword1', $user->fresh()->password)
        );
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::create([
            'name'      => 'Test',
            'email'     => 'test@reversso.com',
            'password'  => 'oldpassword1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('users.password.update', $user->id), [
            'password'              => 'newpassword1',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_admin_can_toggle_user_active(): void
    {
        $user = User::create([
            'name'      => 'Test',
            'email'     => 'test@reversso.com',
            'password'  => 'password123',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('users.toggle-active', $user->id));

        $response->assertRedirect(route('users.index'));
        $this->assertFalse((bool) $user->fresh()->is_active);

        // Toggle back
        $this->actingAs($this->admin)->post(route('users.toggle-active', $user->id));
        $this->assertTrue((bool) $user->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.toggle-active', $this->admin->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue((bool) $this->admin->fresh()->is_active);
    }

    public function test_conductor_cannot_access_users_module(): void
    {
        $conductor = User::create([
            'name'      => 'Driver',
            'email'     => 'driver@reversso.com',
            'password'  => 'password123',
            'is_active' => true,
        ]);
        $conductor->assignRole($this->conductorRole);

        $response = $this->actingAs($conductor)->get(route('users.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name'     => 'Duplicate',
            'email'    => 'admin@reversso.com', // already exists
            'password' => 'password123',
            'role'     => 'conductor',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
