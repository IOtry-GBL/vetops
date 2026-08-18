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

function makePetOwnerPrescriptionDoseFixture(array $overrides = []): array
{
    $owner = User::factory()->create([
        'role' => 'Pet Owner',
    ]);

    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Toffee',
        'species' => 'Dog',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'status' => 'Completed',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => 'Itching',
        'ai_guidance_summary' => null,
        'consultation_notes' => 'Monitor daily.',
        'consultation_date' => now()->subDay(),
        'status' => 'Completed',
    ]);

    $record = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => 'Mild dermatitis',
        'treatment_plan' => 'Daily medication for one week.',
        'vaccination_notes' => null,
        'follow_up_date' => now()->addWeek()->toDateString(),
    ]);

    $prescription = EPrescription::create([
        'record_id' => $record->record_id,
        'medication_name' => $overrides['medication_name'] ?? 'Cetirizine',
        'dosage' => $overrides['dosage'] ?? '10 mg',
        'frequency' => $overrides['frequency'] ?? 'Twice daily',
        'duration' => $overrides['duration'] ?? '5 days',
        'issued_at' => $overrides['issued_at'] ?? now()->subHours(2),
    ]);

    $adherenceLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => $overrides['scheduled_datetime'] ?? now()->subMinutes(30),
        'confirmation_deadline' => $overrides['confirmation_deadline'] ?? now()->addHours(2)->addMinutes(30),
        'intake_status' => $overrides['intake_status'] ?? 'Pending',
        'is_notified' => $overrides['is_notified'] ?? false,
    ]);

    if (($overrides['with_notification'] ?? true) === true) {
        AdherenceNotification::create([
            'user_id' => $owner->user_id,
            'adherence_id' => $adherenceLog->adherence_id,
            'medication_name' => $prescription->medication_name,
            'dosage' => $prescription->dosage,
            'scheduled_at' => $adherenceLog->scheduled_datetime,
            'confirmation_deadline' => $adherenceLog->confirmation_deadline,
            'status' => 'Pending',
        ]);
    }

    return compact('owner', 'vet', 'pet', 'record', 'prescription', 'adherenceLog');
}

test('pet owner prescriptions page shows direct dose confirmation controls', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'));

    $fixture = makePetOwnerPrescriptionDoseFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 4, 30, 0, 'UTC'),
        'with_notification' => true,
    ]);

    AdherenceLog::create([
        'prescription_id' => $fixture['prescription']->prescription_id,
        'scheduled_datetime' => Carbon::create(2026, 4, 21, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 21, 4, 30, 0, 'UTC'),
        'intake_status' => 'Pending',
        'is_notified' => false,
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.prescriptions'));

    $response->assertOk()
        ->assertSee('Dose Confirmation')
        ->assertSee('Next Dose')
        ->assertSee('Ready To Confirm')
        ->assertSee('Confirm Dose')
        ->assertSee('Apr 20, 2026 9:30 AM PH')
        ->assertDontSee('Apr 21, 2026 9:30 AM PH')
        ->assertSee($fixture['prescription']->medication_name);

    Carbon::setTestNow();
});

test('active prescriptions are listed first by oldest issued time and create next dose notification', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'));

    $finished = makePetOwnerPrescriptionDoseFixture([
        'medication_name' => 'Finished Med',
        'issued_at' => Carbon::create(2026, 4, 20, 1, 0, 0, 'UTC'),
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 4, 30, 0, 'UTC'),
        'intake_status' => 'Taken',
        'with_notification' => false,
    ]);

    $olderActive = EPrescription::create([
        'record_id' => $finished['record']->record_id,
        'medication_name' => 'Older Active Med',
        'dosage' => '5 mg',
        'frequency' => 'Once daily',
        'duration' => '2 days',
        'issued_at' => Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'),
    ]);

    $olderActiveLog = AdherenceLog::create([
        'prescription_id' => $olderActive->prescription_id,
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 5, 0, 0, 'UTC'),
        'intake_status' => 'Pending',
        'is_notified' => false,
    ]);

    $newerActive = EPrescription::create([
        'record_id' => $finished['record']->record_id,
        'medication_name' => 'Newer Active Med',
        'dosage' => '15 mg',
        'frequency' => 'Once daily',
        'duration' => '2 days',
        'issued_at' => Carbon::create(2026, 4, 20, 3, 0, 0, 'UTC'),
    ]);

    AdherenceLog::create([
        'prescription_id' => $newerActive->prescription_id,
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 3, 0, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 6, 0, 0, 'UTC'),
        'intake_status' => 'Pending',
        'is_notified' => false,
    ]);

    $response = $this->actingAs($finished['owner'])
        ->get(route('pet-owner.prescriptions'));

    $response->assertOk()
        ->assertSeeInOrder([
            'Older Active Med',
            'Newer Active Med',
            'Finished Med',
        ])
        ->assertDontSee('next-dose-card');

    $this->assertDatabaseHas('adherence_notifications', [
        'adherence_id' => $olderActiveLog->adherence_id,
        'status' => 'Pending',
    ]);

    Carbon::setTestNow();
});

test('pet owner prescriptions page marks expired pending doses as missed', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 0, 0, 'UTC'));

    $fixture = makePetOwnerPrescriptionDoseFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 4, 30, 0, 'UTC'),
        'with_notification' => false,
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.prescriptions'));

    $response->assertOk()
        ->assertSee('No active confirmation')
        ->assertDontSee('Window Closed')
        ->assertDontSee('Dose was not confirmed in time.');

    $this->assertDatabaseHas('adherence_logs', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'intake_status' => 'Missed',
    ]);

    $this->assertDatabaseHas('adherence_notifications', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'status' => 'Missed',
    ]);

    Carbon::setTestNow();
});

test('pet owner prescriptions page advances schedule to next pending dose day', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'));

    $fixture = makePetOwnerPrescriptionDoseFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 4, 30, 0, 'UTC'),
        'intake_status' => 'Taken',
        'with_notification' => false,
    ]);

    $nextLog = AdherenceLog::create([
        'prescription_id' => $fixture['prescription']->prescription_id,
        'scheduled_datetime' => Carbon::create(2026, 4, 21, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 21, 4, 30, 0, 'UTC'),
        'intake_status' => 'Pending',
        'is_notified' => false,
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.prescriptions'));

    $response->assertOk()
        ->assertSee('Apr 21, 2026 9:30 AM PH')
        ->assertDontSee('Apr 20, 2026 9:30 AM PH');

    $this->assertDatabaseHas('adherence_notifications', [
        'adherence_id' => $nextLog->adherence_id,
        'status' => 'Pending',
    ]);

    Carbon::setTestNow();
});

test('pet owner can confirm a due dose directly from the prescriptions page', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'));

    $fixture = makePetOwnerPrescriptionDoseFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 4, 30, 0, 'UTC'),
        'with_notification' => false,
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->from(route('pet-owner.prescriptions'))
        ->post(route('adherence.confirm-dose', $fixture['adherenceLog']));

    $response->assertRedirect(route('pet-owner.prescriptions'));

    $this->assertDatabaseHas('adherence_logs', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'intake_status' => 'Taken',
    ]);

    $this->assertDatabaseHas('adherence_notifications', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'status' => 'Confirmed',
    ]);

    Carbon::setTestNow();
});

test('confirmed dose is hidden from confirmation ui but remains stored', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 2, 0, 0, 'UTC'));

    $fixture = makePetOwnerPrescriptionDoseFixture([
        'scheduled_datetime' => Carbon::create(2026, 4, 20, 1, 30, 0, 'UTC'),
        'confirmation_deadline' => Carbon::create(2026, 4, 20, 4, 30, 0, 'UTC'),
        'with_notification' => false,
    ]);

    $this->actingAs($fixture['owner'])
        ->from(route('pet-owner.prescriptions'))
        ->post(route('adherence.confirm-dose', $fixture['adherenceLog']));

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.prescriptions'));

    $response->assertOk()
        ->assertSee('No active confirmation')
        ->assertDontSee('Apr 20, 2026 9:30 AM PH')
        ->assertDontSee('Confirm Dose');

    $this->assertDatabaseHas('adherence_logs', [
        'adherence_id' => $fixture['adherenceLog']->adherence_id,
        'intake_status' => 'Taken',
    ]);

    $this->assertDatabaseCount('adherence_logs', 1);

    Carbon::setTestNow();
});
