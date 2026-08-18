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

function makeStaffReportFixture(array $overrides = []): array
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
        'pet_name' => $overrides['pet_name'] ?? 'Peanut',
        'species' => $overrides['species'] ?? 'Dog',
        'breed' => $overrides['breed'] ?? 'Mixed Breed',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => $overrides['appointment_date'] ?? now()->toDateString(),
        'appointment_time' => $overrides['appointment_time'] ?? '10:00',
        'consultation_mode' => $overrides['consultation_mode'] ?? 'In-clinic',
        'reason_for_visit' => $overrides['reason_for_visit'] ?? 'Persistent scratching',
        'status' => $overrides['appointment_status'] ?? 'Completed',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => $overrides['chief_complaint'] ?? 'Skin irritation around the neck',
        'ai_guidance_summary' => null,
        'consultation_notes' => $overrides['consultation_notes'] ?? 'Patient remained stable and cooperative.',
        'consultation_date' => $overrides['consultation_date'] ?? now(),
        'status' => $overrides['consultation_status'] ?? 'Completed',
    ]);

    $medicalRecord = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => $overrides['diagnosis'] ?? 'Seasonal dermatitis',
        'treatment_plan' => $overrides['treatment_plan'] ?? 'Use medicated shampoo twice weekly.',
        'vaccination_notes' => $overrides['vaccination_notes'] ?? 'Vaccinations are current.',
        'follow_up_date' => $overrides['follow_up_date'] ?? now()->addWeek()->toDateString(),
    ]);

    $prescription = EPrescription::create([
        'record_id' => $medicalRecord->record_id,
        'medication_name' => $overrides['medication_name'] ?? 'Cetirizine',
        'dosage' => $overrides['dosage'] ?? '10 mg',
        'frequency' => $overrides['frequency'] ?? 'Once daily',
        'duration' => $overrides['duration'] ?? '14 days',
        'issued_at' => $overrides['issued_at'] ?? now(),
    ]);

    $takenLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => now()->subHours(8),
        'intake_status' => 'Taken',
        'confirmation_time' => now()->subHours(7),
        'remarks' => 'Given with food.',
    ]);

    $missedLog = AdherenceLog::create([
        'prescription_id' => $prescription->prescription_id,
        'scheduled_datetime' => now()->subHours(2),
        'intake_status' => 'Missed',
        'confirmation_time' => null,
        'remarks' => 'Owner forgot evening dose.',
    ]);

    return compact('staff', 'vet', 'owner', 'pet', 'appointment', 'consultation', 'medicalRecord', 'prescription', 'takenLog', 'missedLog');
}

test('staff reports dashboard loads with intake status metrics', function () {
    $fixture = makeStaffReportFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.reports'));

    $response->assertOk()
        ->assertViewIs('staff.reports.dashboard')
        ->assertSee('Reports')
        ->assertSee('Taken')
        ->assertSee('Missed')
        ->assertSee('Cancelled')
        ->assertDontSee('Completion Rate')
        ->assertViewHas('totalConsultations', 1)
        ->assertViewHas('completedConsultations', 1)
        ->assertViewHas('totalMedicalRecords', 1);
});

test('staff appointment report loads', function () {
    $fixture = makeStaffReportFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.reports.appointments'));

    $response->assertOk()
        ->assertViewIs('staff.reports.appointments')
        ->assertSee('Appointment Report')
        ->assertSee('Peanut')
        ->assertSee('Cancelled')
        ->assertSee('Missed');
});

test('staff prescription report loads with current compliance calculation', function () {
    $fixture = makeStaffReportFixture();

    $response = $this->actingAs($fixture['staff'])
        ->get(route('staff.reports.prescriptions'));

    $response->assertOk()
        ->assertViewIs('staff.reports.prescriptions')
        ->assertSee('Prescription Report')
        ->assertSee('Cetirizine')
        ->assertSee('50%');
});
