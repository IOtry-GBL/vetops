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

function makePetOwnerMedicalRecordsFixture(array $overrides = []): array
{
    $owner = User::factory()->create([
        'role' => 'Pet Owner',
        'full_name' => 'Mia Owner',
    ]);

    $vet = User::factory()->create([
        'role' => 'Veterinarian',
        'full_name' => 'Dr. Grace Vet',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => $overrides['pet_name'] ?? 'Coco',
        'species' => $overrides['species'] ?? 'Dog',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'status' => 'Completed',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => $overrides['chief_complaint'] ?? 'Itching and redness',
        'ai_guidance_summary' => null,
        'consultation_notes' => $overrides['consultation_notes'] ?? 'Monitor response to treatment.',
        'consultation_date' => now()->subDay(),
        'status' => 'Completed',
    ]);

    $record = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => $overrides['diagnosis'] ?? 'Allergic dermatitis',
        'treatment_plan' => $overrides['treatment_plan'] ?? 'Daily medication and bath protocol.',
        'vaccination_notes' => 'Vaccines current.',
        'follow_up_date' => now()->addWeek()->toDateString(),
    ]);

    $prescription = EPrescription::create([
        'record_id' => $record->record_id,
        'medication_name' => 'Cetirizine',
        'dosage' => '10 mg',
        'frequency' => 'Once daily',
        'duration' => '7 days',
        'issued_at' => now()->subHours(3),
    ]);

    $adherenceLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => Carbon::now()->subMinutes(30),
        'confirmation_deadline' => Carbon::now()->addHours(2),
        'intake_status' => 'Pending',
        'is_notified' => true,
    ]);

    AdherenceNotification::create([
        'user_id' => $owner->user_id,
        'adherence_id' => $adherenceLog->adherence_id,
        'medication_name' => $prescription->medication_name,
        'dosage' => $prescription->dosage,
        'scheduled_at' => $adherenceLog->scheduled_datetime,
        'confirmation_deadline' => $adherenceLog->confirmation_deadline,
        'status' => 'Pending',
    ]);

    return compact('owner', 'vet', 'pet', 'appointment', 'consultation', 'record', 'prescription', 'adherenceLog');
}

test('pet owner medical records page shows only their own records', function () {
    $fixture = makePetOwnerMedicalRecordsFixture([
        'pet_name' => 'Coco',
        'diagnosis' => 'Allergic dermatitis',
    ]);

    $otherOwner = User::factory()->create([
        'role' => 'Pet Owner',
    ]);

    $otherPet = Pet::factory()->create([
        'user_id' => $otherOwner->user_id,
        'pet_name' => 'Shadow',
    ]);

    $otherAppointment = Appointment::factory()->create([
        'pet_id' => $otherPet->pet_id,
        'status' => 'Completed',
    ]);

    $otherConsultation = Consultation::create([
        'appointment_id' => $otherAppointment->appointment_id,
        'veterinarian_id' => $fixture['vet']->user_id,
        'chief_complaint' => 'Ear infection',
        'ai_guidance_summary' => null,
        'consultation_notes' => null,
        'consultation_date' => now()->subDay(),
        'status' => 'Completed',
    ]);

    MedicalRecord::create([
        'pet_id' => $otherPet->pet_id,
        'consultation_id' => $otherConsultation->consultation_id,
        'diagnosis' => 'Ear infection',
        'treatment_plan' => 'Ear drops for five days.',
        'vaccination_notes' => null,
        'follow_up_date' => now()->addDays(5)->toDateString(),
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.medical-records'));

    $response->assertOk()
        ->assertViewIs('pet-owner.medical-records')
        ->assertSee('Medical Records')
        ->assertSee('Coco')
        ->assertSee('Allergic dermatitis')
        ->assertDontSee('Shadow');
});

test('pet owner can open medical record details', function () {
    $fixture = makePetOwnerMedicalRecordsFixture([
        'pet_name' => 'Coco',
        'diagnosis' => 'Allergic dermatitis',
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.medical-records.details', $fixture['record']));

    $response->assertOk()
        ->assertViewIs('pet-owner.medical-record-details')
        ->assertSee('Medical Record Details')
        ->assertSee('Coco')
        ->assertSee('Allergic dermatitis')
        ->assertSee('Cetirizine');
});

test('pet owner reminders dashboard loads and exposes confirm intake action', function () {
    $fixture = makePetOwnerMedicalRecordsFixture();

    $response = $this->actingAs($fixture['owner'])
        ->get(route('adherence.dashboard'));

    $response->assertOk()
        ->assertSee('Medication Reminders')
        ->assertSee('Active Reminders')
        ->assertSee('Confirm Intake')
        ->assertSee('Confirmation Rate');
});
