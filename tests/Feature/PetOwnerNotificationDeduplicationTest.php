<?php

use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('pet owner notifications collapse duplicate appointment status rows', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 24, 6, 47, 0, 'UTC'));

    $owner = User::factory()->create([
        'role' => 'Pet Owner',
        'first_name' => 'Client',
        'full_name' => 'Client',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Max',
        'species' => 'Dog',
    ]);

    $appointment = Appointment::create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => '2026-06-24',
        'appointment_time' => '06:47:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Follow-up',
        'status' => 'Missed',
    ]);

    AppointmentNotification::recordStatus($appointment->appointment_id, 'Missed');
    AppointmentNotification::recordStatus($appointment->appointment_id, 'Missed');

    $this->assertDatabaseCount('appointment_notifications', 1);

    $response = $this->actingAs($owner)
        ->get(route('pet-owner.notifications'));

    $response->assertOk()
        ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 1)
        ->assertSee('missed your scheduled appointment')
        ->assertSee('Max');

    Carbon::setTestNow();
});
