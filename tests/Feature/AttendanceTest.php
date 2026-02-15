<?php

namespace Tests\Feature;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Logistics\Attendance\Infrastructure\Models\UserAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
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

    public function test_conductor_can_check_in(): void
    {
        $response = $this->actingAs($this->conductor)->post(route('attendance.checkin'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_attendance', [
            'user_id' => $this->conductor->id,
            'status'  => 'active',
        ]);
    }

    public function test_conductor_cannot_check_in_twice(): void
    {
        // First check-in
        $this->actingAs($this->conductor)->post(route('attendance.checkin'));

        // Second check-in should fail
        $response = $this->actingAs($this->conductor)->post(route('attendance.checkin'));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Only one record
        $this->assertEquals(1, UserAttendance::where('user_id', $this->conductor->id)->count());
    }

    public function test_conductor_can_check_out(): void
    {
        // Check in first
        $this->actingAs($this->conductor)->post(route('attendance.checkin'));

        // Check out
        $response = $this->actingAs($this->conductor)->post(route('attendance.checkout'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $attendance = UserAttendance::where('user_id', $this->conductor->id)->first();
        $this->assertEquals('completed', $attendance->status);
        $this->assertNotNull($attendance->check_out);
    }

    public function test_conductor_cannot_check_out_without_check_in(): void
    {
        $response = $this->actingAs($this->conductor)->post(route('attendance.checkout'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_check_in_with_holiday_flag(): void
    {
        $response = $this->actingAs($this->conductor)->post(route('attendance.checkin'), [
            'is_holiday' => '1',
        ]);

        $response->assertRedirect();

        $attendance = UserAttendance::where('user_id', $this->conductor->id)->first();
        $this->assertTrue((bool) $attendance->is_holiday);
    }
}
