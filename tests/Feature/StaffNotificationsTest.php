<?php

use App\Models\Appointment;
use App\Models\AdherenceLog;
use App\Models\AdherenceNotification;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\StaffNotificationDismissal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeStaffNotificationAppointmentFixture(array $overrides = []): array
{
    $owner = User::factory()->create([
        'role' => 'Pet Owner',
        'full_name' => $overrides['owner_name'] ?? 'Jamie Owner',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => $overrides['pet_name'] ?? 'Milo',
        'species' => $overrides['species'] ?? 'Dog',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => $overrides['appointment_date'] ?? '2026-04-21',
        'appointment_time' => $overrides['appointment_time'] ?? '09:00',
        'consultation_mode' => $overrides['consultation_mode'] ?? 'In-clinic',
        'status' => $overrides['status'] ?? 'Pending',
    ]);

    if (isset($overrides['created_at']) || isset($overrides['updated_at'])) {
        $appointment->forceFill([
            'created_at' => $overrides['created_at'] ?? $appointment->created_at,
            'updated_at' => $overrides['updated_at'] ?? $appointment->updated_at,
        ])->saveQuietly();
    }

    return compact('owner', 'pet', 'appointment');
}

test('staff notifications page shows live appointment activity sections from real appointment data', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 4, 0, 0, 'UTC'));

    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    $newEntry = makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Nori',
        'created_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'status' => 'Pending',
    ]);

    $cancelledToday = makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Maple',
        'status' => 'Cancelled',
        'created_at' => Carbon::create(2026, 4, 17, 2, 0, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 1, 15, 0, 'UTC'),
    ]);

    $didNotArrive = makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Atlas',
        'status' => 'Missed',
        'created_at' => Carbon::create(2026, 4, 17, 2, 0, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 2, 45, 0, 'UTC'),
    ]);

    $staleAppointment = makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Oldie',
        'status' => 'Cancelled',
        'created_at' => Carbon::create(2026, 4, 15, 1, 0, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 16, 1, 0, 0, 'UTC'),
    ]);

    $response = $this->actingAs($staff)->get(route('staff.notifications'));

    $response->assertOk()
        ->assertViewIs('staff.notifications')
        ->assertSee('Live Staff Notifications')
        ->assertSee('New Appointment')
        ->assertSee('Appointment Cancelled')
        ->assertSee('Did Not Arrive')
        ->assertSee($newEntry['pet']->name)
        ->assertSee($cancelledToday['pet']->name)
        ->assertSee($didNotArrive['pet']->name)
        ->assertDontSee($staleAppointment['pet']->name)
        ->assertViewHas('newAppointmentEntries', fn ($appointments) => $appointments->pluck('appointment_id')->contains($newEntry['appointment']->appointment_id))
        ->assertViewHas('cancelledTodayAppointments', fn ($appointments) => $appointments->pluck('appointment_id')->contains($cancelledToday['appointment']->appointment_id))
        ->assertViewHas('recentDidNotArriveAppointments', fn ($appointments) => $appointments->pluck('appointment_id')->contains($didNotArrive['appointment']->appointment_id));

    Carbon::setTestNow();
});

test('staff notification view action is only shown for new appointments and adherence alerts', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 4, 0, 0, 'UTC'));

    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Nori',
        'created_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'status' => 'Pending',
    ]);

    makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Maple',
        'status' => 'Cancelled',
        'created_at' => Carbon::create(2026, 4, 17, 2, 0, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 1, 15, 0, 'UTC'),
    ]);

    $adherence = makeStaffAdherenceNotificationFixture([
        'pet_name' => 'Pip',
        'scheduled_at' => Carbon::create(2026, 4, 20, 3, 0, 0, 'UTC'),
        'confirmed_at' => Carbon::create(2026, 4, 20, 3, 20, 0, 'UTC'),
    ]);

    $response = $this->actingAs($staff)->get(route('staff.notifications'));

    $response->assertOk()
        ->assertSee('New Appointment')
        ->assertSee('Appointment Cancelled')
        ->assertSee('Adherence')
        ->assertSee('taken as scheduled')
        ->assertSee(route('staff.appointments.confirm', 1), false)
        ->assertSee(route('staff.prescriptions.details', $adherence['prescription']), false)
        ->assertDontSee('Review Cancellation');

    expect(substr_count($response->getContent(), '>View</a>'))->toBe(2);

    Carbon::setTestNow();
});

test('staff adherence notifications only show doses confirmed as taken', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 4, 0, 0, 'UTC'));

    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    makeStaffAdherenceNotificationFixture([
        'pet_name' => 'TakenPet',
        'scheduled_at' => Carbon::create(2026, 4, 20, 3, 0, 0, 'UTC'),
        'confirmed_at' => Carbon::create(2026, 4, 20, 3, 15, 0, 'UTC'),
        'status' => 'Confirmed',
    ]);

    makeStaffAdherenceNotificationFixture([
        'pet_name' => 'PendingPet',
        'scheduled_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'status' => 'Pending',
    ]);

    $this->actingAs($staff)
        ->get(route('staff.notifications'))
        ->assertOk()
        ->assertSee('TakenPet')
        ->assertSee('taken as scheduled')
        ->assertDontSee('PendingPet');

    Carbon::setTestNow();
});

test('staff can delete a notification from their own feed', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 4, 0, 0, 'UTC'));

    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    $fixture = makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Nori',
        'created_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 3, 30, 0, 'UTC'),
        'status' => 'Pending',
    ]);

    $response = $this->actingAs($staff)
        ->delete(route('staff.notifications.delete', ['new-appointment', $fixture['appointment']->appointment_id]));

    $response->assertRedirect(route('staff.notifications'));

    $this->assertDatabaseHas('staff_notification_dismissals', [
        'user_id' => $staff->user_id,
        'notification_key' => 'new-appointment:' . $fixture['appointment']->appointment_id,
    ]);

    $this->actingAs($staff)
        ->get(route('staff.notifications'))
        ->assertOk()
        ->assertDontSee('Nori');

    Carbon::setTestNow();
});

test('staff notifications feed endpoint returns refreshable html fragments', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 4, 0, 0, 'UTC'));

    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    makeStaffNotificationAppointmentFixture([
        'pet_name' => 'Pebble',
        'created_at' => Carbon::create(2026, 4, 20, 3, 45, 0, 'UTC'),
        'updated_at' => Carbon::create(2026, 4, 20, 3, 45, 0, 'UTC'),
        'status' => 'Pending',
    ]);

    $response = $this->actingAs($staff)
        ->getJson(route('staff.notifications.feed'));

    $response->assertOk()
        ->assertJsonStructure([
            'summary_html',
            'feed_html',
            'synced_at',
        ]);

    expect($response->json('summary_html'))->toContain('New Entries');
    expect($response->json('feed_html'))->toContain('Pebble');
    expect($response->json('feed_html'))->toContain('Open Appointment');

    Carbon::setTestNow();
});

function makeStaffAdherenceNotificationFixture(array $overrides = []): array
{
    $owner = User::factory()->create([
        'role' => 'Pet Owner',
        'full_name' => $overrides['owner_name'] ?? 'Ari Owner',
    ]);

    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => $overrides['pet_name'] ?? 'Pip',
        'species' => 'Cat',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'status' => 'Completed',
    ]);
    $appointment->forceFill([
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ])->saveQuietly();

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => 'Coughing',
        'consultation_date' => now()->subDay(),
        'status' => 'Completed',
    ]);

    $record = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => 'Mild cough',
        'treatment_plan' => 'Medication and rest.',
    ]);

    $prescription = EPrescription::create([
        'record_id' => $record->record_id,
        'medication_name' => 'Doxycycline',
        'dosage' => '5 mg',
        'frequency' => 'Once daily',
        'duration' => '3 days',
        'issued_at' => now()->subHours(4),
    ]);

    $adherenceLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => $overrides['scheduled_at'] ?? now()->subHour(),
        'confirmation_deadline' => now()->addHours(2),
        'intake_status' => ($overrides['status'] ?? 'Confirmed') === 'Confirmed' ? 'Taken' : 'Pending',
        'confirmation_time' => $overrides['confirmed_at'] ?? ((($overrides['status'] ?? 'Confirmed') === 'Confirmed') ? now() : null),
        'is_notified' => true,
    ]);

    $notification = AdherenceNotification::create([
        'user_id' => $owner->user_id,
        'adherence_id' => $adherenceLog->adherence_id,
        'medication_name' => $prescription->medication_name,
        'dosage' => $prescription->dosage,
        'scheduled_at' => $overrides['scheduled_at'] ?? now()->subHour(),
        'confirmation_deadline' => now()->addHours(2),
        'status' => $overrides['status'] ?? 'Confirmed',
        'confirmed_at' => $overrides['confirmed_at'] ?? ((($overrides['status'] ?? 'Confirmed') === 'Confirmed') ? now() : null),
    ]);

    return compact('owner', 'vet', 'pet', 'appointment', 'record', 'prescription', 'adherenceLog', 'notification');
}
