<?php

use App\Models\AdherenceLog;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStaffMedicalFixture(array $overrides = []): array
{
    $staff = User::factory()->create([
        'role' => 'Staff',
    ]);

    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $owner = User::factory()->create([
        'role' => 'Pet Owner',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => $overrides['pet_name'] ?? 'Milo',
        'species' => $overrides['species'] ?? 'Dog',
        'breed' => $overrides['breed'] ?? 'Shih Tzu',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => $overrides['appointment_date'] ?? now()->subDay()->toDateString(),
        'appointment_time' => $overrides['appointment_time'] ?? '09:30',
        'consultation_mode' => $overrides['consultation_mode'] ?? 'In-clinic',
        'reason_for_visit' => $overrides['reason_for_visit'] ?? 'Skin irritation',
        'status' => $overrides['appointment_status'] ?? 'Completed',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => $overrides['chief_complaint'] ?? 'Redness and scratching around the neck',
        'ai_guidance_summary' => null,
        'consultation_notes' => $overrides['consultation_notes'] ?? 'Patient remained stable during the visit.',
        'consultation_date' => $overrides['consultation_date'] ?? now()->subDay(),
        'status' => $overrides['consultation_status'] ?? 'Completed',
    ]);

    $medicalRecord = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => $overrides['diagnosis'] ?? 'Seasonal dermatitis',
        'treatment_plan' => $overrides['treatment_plan'] ?? 'Use medicated shampoo twice weekly.',
        'vaccination_notes' => $overrides['vaccination_notes'] ?? 'Vaccinations remain up to date.',
        'follow_up_date' => $overrides['follow_up_date'] ?? now()->addWeek()->toDateString(),
    ]);

    $prescription = EPrescription::create([
        'record_id' => $medicalRecord->record_id,
        'medication_name' => $overrides['medication_name'] ?? 'Cetirizine',
        'dosage' => $overrides['dosage'] ?? '10 mg',
        'frequency' => $overrides['frequency'] ?? 'Once daily',
        'duration' => $overrides['duration'] ?? '14 days',
        'issued_at' => $overrides['issued_at'] ?? now()->subHours(3),
    ]);

    $adherenceLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => now()->subHours(12),
        'intake_status' => 'Taken',
        'confirmation_time' => now()->subHours(11),
        'remarks' => $overrides['remarks'] ?? 'Given after breakfast.',
    ]);

    return compact('staff', 'vet', 'owner', 'pet', 'appointment', 'consultation', 'medicalRecord', 'prescription', 'adherenceLog');
}

test('staff can view the prescriptions index page', function () {
    $fixture = makeStaffMedicalFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.prescriptions'));

    $response->assertOk()
        ->assertViewIs('staff.prescriptions.index')
        ->assertSee('Cetirizine')
        ->assertSee('Milo');
});

test('staff can view prescription details', function () {
    $fixture = makeStaffMedicalFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.prescriptions.details', $fixture['prescription']->prescription_id));

    $response->assertOk()
        ->assertViewIs('staff.prescriptions.details')
        ->assertSee('Cetirizine')
        ->assertSee('Seasonal dermatitis')
        ->assertSee('Taken');
});

test('staff can view the medical records index page', function () {
    $fixture = makeStaffMedicalFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.medical-records'));

    $response->assertOk()
        ->assertViewIs('medical-records.staff-index')
        ->assertSee('Milo')
        ->assertSee('Seasonal dermatitis');
});

test('staff can view medical record details', function () {
    $fixture = makeStaffMedicalFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.medical-records.details', $fixture['medicalRecord']->record_id));

    $response->assertOk()
        ->assertViewIs('medical-records.staff-details')
        ->assertSee('Seasonal dermatitis')
        ->assertSee('Use medicated shampoo twice weekly.')
        ->assertSee('Cetirizine');
});
