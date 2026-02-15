<?php

namespace Tests\Feature;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Logistics\Attendance\Infrastructure\Models\UserAttendance;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
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

    private function createActiveAttendance(): void
    {
        UserAttendance::create([
            'user_id'  => $this->conductor->id,
            'check_in' => now(),
            'status'   => 'active',
        ]);
    }

    public function test_conductor_can_start_trip_with_active_attendance(): void
    {
        $this->createActiveAttendance();

        $response = $this->actingAs($this->conductor)->post(route('logistics.start'), [
            'vehicle_plate'  => 'ABC123',
            'origin'         => 'Bogota',
            'start_odometer' => 10000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('time_tracking', [
            'user_id'       => $this->conductor->id,
            'vehicle_plate' => 'ABC123',
            'origin'        => 'Bogota',
        ]);
    }

    public function test_conductor_cannot_start_trip_without_attendance(): void
    {
        // No active attendance
        $response = $this->actingAs($this->conductor)->post(route('logistics.start'), [
            'vehicle_plate'  => 'ABC123',
            'origin'         => 'Bogota',
            'start_odometer' => 10000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([], null, 'startTrip');
    }

    public function test_conductor_can_end_trip(): void
    {
        $this->createActiveAttendance();

        // Start trip
        TimeTracking::create([
            'user_id'        => $this->conductor->id,
            'start_time'     => now(),
            'vehicle_plate'  => 'ABC123',
            'origin'         => 'Bogota',
            'start_odometer' => 10000,
        ]);

        $response = $this->actingAs($this->conductor)->post(route('logistics.end'), [
            'destination'  => 'Medellin',
            'end_odometer' => 10500,
            'observations' => 'Sin novedades',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $trip = TimeTracking::where('user_id', $this->conductor->id)->first();
        $this->assertEquals('Medellin', $trip->destination);
        $this->assertNotNull($trip->end_time);
    }

    public function test_end_odometer_must_be_valid(): void
    {
        $this->createActiveAttendance();

        // Start trip
        TimeTracking::create([
            'user_id'        => $this->conductor->id,
            'start_time'     => now(),
            'vehicle_plate'  => 'ABC123',
            'origin'         => 'Bogota',
            'start_odometer' => 10000,
        ]);

        // End with odometer less than start
        $response = $this->actingAs($this->conductor)->post(route('logistics.end'), [
            'destination'  => 'Medellin',
            'end_odometer' => 5000, // Less than start
        ]);

        $response->assertRedirect();
        // Should have errors (either validation or business logic)
        $trip = TimeTracking::where('user_id', $this->conductor->id)->first();
        // Trip should still be open (end_time null) if validation failed
        $this->assertNull($trip->end_time);
    }

    public function test_vehicle_plate_is_uppercased(): void
    {
        $this->createActiveAttendance();

        $this->actingAs($this->conductor)->post(route('logistics.start'), [
            'vehicle_plate'  => 'abc123',
            'origin'         => 'Bogota',
            'start_odometer' => 10000,
        ]);

        $this->assertDatabaseHas('time_tracking', [
            'vehicle_plate' => 'ABC123',
        ]);
    }

    public function test_start_trip_validation_requires_fields(): void
    {
        $this->createActiveAttendance();

        $response = $this->actingAs($this->conductor)->post(route('logistics.start'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors([], null, 'startTrip');
    }
}
