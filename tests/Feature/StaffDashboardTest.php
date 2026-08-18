<?php

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff dashboard counts only approved appointments for today and keeps pending separate', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 30, 16, 30, 0, 'UTC'));

    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    $owner = User::factory()->create([
        'role' => 'Pet Owner',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Mochi',
        'species' => 'Cat',
        'breed' => 'Persian',
    ]);

    $approvedToday = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => '2026-07-31',
        'appointment_time' => '09:00:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Routine check-up',
        'status' => 'Approved',
    ]);

    $pendingToday = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => '2026-07-31',
        'appointment_time' => '11:00:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Vaccination',
        'status' => 'Pending',
    ]);

    $approvedTomorrow = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => '2026-08-01',
        'appointment_time' => '10:00:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Follow-up',
        'status' => 'Approved',
    ]);

    $this->actingAs($staff)
        ->get(route('staff.dashboard'))
        ->assertOk()
        ->assertViewIs('staff.dashboard')
        ->assertViewHas('todayAppointments', 1)
        ->assertViewHas('pending', 1)
        ->assertViewHas('todayListAppointments', function ($appointments) use ($approvedToday, $pendingToday, $approvedTomorrow) {
            return $appointments->pluck('appointment_id')->contains($approvedToday->appointment_id)
                && ! $appointments->pluck('appointment_id')->contains($pendingToday->appointment_id)
                && ! $appointments->pluck('appointment_id')->contains($approvedTomorrow->appointment_id);
        });

    Carbon::setTestNow();
});
