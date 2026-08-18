<?php

use App\Models\AdherenceLog;
use App\Models\AdherenceNotification;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeAdherenceNotificationFixture(array $overrides = []): array
{
    $owner = User::factory()->create([
        'role' => 'Pet Owner',
    ]);

    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'status' => 'Completed',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => 'Follow-up medication review',
        'ai_guidance_summary' => null,
        'consultation_notes' => 'Stable condition.',
        'consultation_date' => now()->subDay(),
        'status' => 'Completed',
    ]);

    $record = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => 'Dermatitis',
        'treatment_plan' => 'Continue oral medication.',
        'vaccination_notes' => null,
        'follow_up_date' => now()->addWeek()->toDateString(),
    ]);

    $prescription = EPrescription::create([
        'record_id' => $record->record_id,
        'medication_name' => 'Cetirizine',
        'dosage' => '10 mg',
        'frequency' => 'Once daily',
        'duration' => '7 days',
        'issued_at' => now()->subHour(),
    ]);

    $adherenceLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => $overrides['scheduled_datetime'] ?? now()->addHour(),
        'confirmation_deadline' => $overrides['confirmation_deadline'] ?? now()->addHours(4),
        'intake_status' => 'Pending',
        'is_notified' => true,
    ]);

    $notification = AdherenceNotification::create([
        'user_id' => $owner->user_id,
        'adherence_id' => $adherenceLog->adherence_id,
        'medication_name' => $prescription->medication_name,
        'dosage' => $prescription->dosage,
        'scheduled_at' => $adherenceLog->scheduled_datetime,
        'confirmation_deadline' => $adherenceLog->confirmation_deadline,
        'status' => 'Pending',
    ]);

    return compact('owner', 'notification', 'adherenceLog');
}

test('pet owner cannot confirm medication before the scheduled dose window opens', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 19, 1, 0, 0, 'UTC'));

    $fixture = makeAdherenceNotificationFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 19, 4, 0, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 19, 7, 0, 0, 'UTC'),
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->postJson(route('adherence.confirm', $fixture['notification']->notification_id));

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Confirmation is not available until the scheduled medication time.',
        ]);

    $this->assertDatabaseHas('adherence_notifications', [
        'notification_id' => $fixture['notification']->notification_id,
        'status' => 'Pending',
    ]);

    $this->assertDatabaseHas('adherence_logs', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'intake_status' => 'Pending',
    ]);

    Carbon::setTestNow();
});

test('pet owner receives a success payload with the updated notification after confirmation', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 19, 4, 30, 0, 'UTC'));

    $fixture = makeAdherenceNotificationFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 19, 4, 0, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 19, 7, 0, 0, 'UTC'),
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->postJson(route('adherence.confirm', $fixture['notification']->notification_id), [
            'notes' => 'Taken with breakfast',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Adherence confirmed successfully',
        ])
        ->assertJsonPath('notification.status', 'Confirmed')
        ->assertJsonPath('notification.notes', 'Taken with breakfast')
        ->assertJsonPath('notification.adherence_id', $fixture['adherenceLog']->adherence_id)
        ->assertJsonPath('notification.user_id', $fixture['owner']->user_id);

    $this->assertDatabaseHas('adherence_notifications', [
        'notification_id' => $fixture['notification']->notification_id,
        'status' => 'Confirmed',
        'notes' => 'Taken with breakfast',
    ]);

    $this->assertDatabaseHas('adherence_logs', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'intake_status' => 'Taken',
    ]);

    Carbon::setTestNow();
});
