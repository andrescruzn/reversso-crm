<?php

namespace Tests\Feature;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $conductor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'display_name' => 'Administrador', 'description' => 'Admin']);
        $conductorRole = Role::create(['name' => 'conductor', 'display_name' => 'Conductor', 'description' => 'Driver']);

        $this->conductor = User::create([
            'name'      => 'Conductor Test',
            'email'     => 'conductor@reversso.com',
            'password'  => 'password123',
            'is_active' => true,
        ]);
        $this->conductor->assignRole($conductorRole);
    }

    public function test_user_can_see_profile(): void
    {
        $response = $this->actingAs($this->conductor)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('Conductor Test');
        $response->assertSee('conductor@reversso.com');
    }

    public function test_user_can_change_own_password(): void
    {
        $response = $this->actingAs($this->conductor)->put(route('profile.password.update'), [
            'current_password'      => 'password123',
            'password'              => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword1', $this->conductor->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $response = $this->actingAs($this->conductor)->put(route('profile.password.update'), [
            'current_password'      => 'wrongpassword',
            'password'              => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $this->conductor->fresh()->password));
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->actingAs($this->conductor)->put(route('profile.password.update'), [
            'current_password'      => 'password123',
            'password'              => 'newpassword1',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->get(route('profile.show'));
        $response->assertRedirect(route('login'));
    }
}
