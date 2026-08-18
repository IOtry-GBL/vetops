<?php

use App\Models\AdherenceLog;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Database\Seeders\ExampleClinicSeeder;
use Database\Seeders\MedicalRecordsWithoutPrescriptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeVeterinarianMedicalRecordFixture(User $vet, array $overrides = []): array
{
    $owner = User::factory()->create([
        'role' => 'Pet Owner',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => $overrides['pet_name'] ?? 'Buddy',
        'species' => $overrides['species'] ?? 'Dog',
        'breed' => $overrides['breed'] ?? 'Shih Tzu',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'consultation_mode' => $overrides['consultation_mode'] ?? 'In-clinic',
        'status' => 'Completed',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => $overrides['chief_complaint'] ?? 'Loss of appetite',
        'ai_guidance_summary' => null,
        'consultation_notes' => $overrides['consultation_notes'] ?? 'Patient is stable and responsive.',
        'consultation_date' => $overrides['consultation_date'] ?? now()->subDay(),
        'status' => 'Completed',
    ]);

    $medicalRecord = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => $overrides['diagnosis'] ?? 'Mild gastritis',
        'treatment_plan' => $overrides['treatment_plan'] ?? 'Small frequent meals and hydration support.',
        'vaccination_notes' => $overrides['vaccination_notes'] ?? 'Vaccination schedule is current.',
        'follow_up_date' => $overrides['follow_up_date'] ?? now()->addWeek()->toDateString(),
    ]);

    $prescription = EPrescription::create([
        'record_id' => $medicalRecord->record_id,
        'medication_name' => $overrides['medication_name'] ?? 'Probiotic Paste',
        'dosage' => $overrides['dosage'] ?? '2 mL',
        'frequency' => $overrides['frequency'] ?? 'Twice daily',
        'duration' => $overrides['duration'] ?? '5 days',
        'issued_at' => $overrides['issued_at'] ?? now()->subHours(2),
    ]);

    if (($overrides['with_adherence'] ?? true) === true) {
        AdherenceLog::create([
            'prescription_id' => $prescription->prescription_id,
            'scheduled_datetime' => $overrides['scheduled_datetime'] ?? now()->subHour(),
            'confirmation_deadline' => $overrides['confirmation_deadline'] ?? now()->addHours(2),
            'intake_status' => $overrides['intake_status'] ?? 'Taken',
            'confirmation_time' => $overrides['confirmation_time'] ?? now()->subMinutes(30),
            'is_notified' => true,
        ]);
    }

    return compact('owner', 'pet', 'appointment', 'consultation', 'medicalRecord', 'prescription');
}

test('veterinarian medical records page loads clinic-wide patient records', function () {
    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $otherVet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $fixture = makeVeterinarianMedicalRecordFixture($vet, [
        'pet_name' => 'Milo',
        'diagnosis' => 'Canine dermatitis',
    ]);

    $otherFixture = makeVeterinarianMedicalRecordFixture($otherVet, [
        'pet_name' => 'Shadow',
        'diagnosis' => 'Ear infection',
    ]);

    $response = $this->actingAs($vet)->get(route('vet.medical-records', [
        'patient' => $fixture['pet']->pet_id,
    ]));

    $response->assertOk()
        ->assertViewIs('medical-records.veterinarian-index')
        ->assertSee('Record List')
        ->assertSee('View')
        ->assertSee(route('vet.medical-records.details', $fixture['medicalRecord']), false)
        ->assertDontSee('Medical Record Report')
        ->assertDontSee('Prescription Records And Adherence Percentage')
        ->assertDontSee('View Prescriptions')
        ->assertDontSee('Open Prescriptions')
        ->assertSee('Milo')
        ->assertSee('Shadow')
        ->assertSee('Canine dermatitis')
        ->assertViewHas('patients', function ($patients) use ($fixture, $otherFixture) {
            $patientIds = $patients->pluck('pet_id');

            return $patientIds->contains($fixture['pet']->pet_id)
                && $patientIds->contains($otherFixture['pet']->pet_id);
        });

    $otherPatientResponse = $this->actingAs($vet)->get(route('vet.medical-records', [
        'patient' => $otherFixture['pet']->pet_id,
    ]));

    $otherPatientResponse->assertOk()
        ->assertSee('Shadow')
        ->assertSee('Ear infection');
});

test('veterinarian can open a specific medical record report from record list', function () {
    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $first = makeVeterinarianMedicalRecordFixture($vet, [
        'pet_name' => 'Milo',
        'diagnosis' => 'Skin irritation',
    ]);

    $second = makeVeterinarianMedicalRecordFixture($vet, [
        'pet_name' => 'Luna',
        'diagnosis' => 'Dental disease',
    ]);

    $this->actingAs($vet)
        ->get(route('vet.medical-records.details', $second['medicalRecord']))
        ->assertOk()
        ->assertViewIs('medical-records.veterinarian-details')
        ->assertSee('Record No. '.$second['medicalRecord']->record_id)
        ->assertSee('Medical Record Report')
        ->assertSee('Pet / Owner Name And Contact Info')
        ->assertSee('Prescription Records And Adherence Percentage')
        ->assertSee('Medical Adherence Record')
        ->assertSee('Total Amount To Be Taken')
        ->assertSee('Percentage')
        ->assertSee('Probiotic Paste')
        ->assertSee('Taken')
        ->assertSee('Dental disease')
        ->assertSee('Luna')
        ->assertDontSee('Search or filter records')
        ->assertDontSee('id="medicalRecordsTable"', false)
        ->assertViewHas('record', function (MedicalRecord $record) use ($second) {
            return $record->record_id === $second['medicalRecord']->record_id;
        });
});

test('veterinarian medical records patients are ordered by latest updated record', function () {
    $vet = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $older = makeVeterinarianMedicalRecordFixture($vet, [
        'pet_name' => 'Older Patient',
    ]);

    $newer = makeVeterinarianMedicalRecordFixture($vet, [
        'pet_name' => 'Newer Patient',
    ]);

    $older['medicalRecord']->forceFill([
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ])->saveQuietly();

    $newer['medicalRecord']->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now(),
    ])->saveQuietly();

    $this->actingAs($vet)
        ->get(route('vet.medical-records'))
        ->assertOk()
        ->assertViewHas('patients', function ($patients) use ($newer, $older) {
            return $patients->first()->pet_id === $newer['pet']->pet_id
                && $patients->last()->pet_id === $older['pet']->pet_id;
        });
});

test('example clinic seeder creates current medical record and prescription data', function () {
    $this->seed(ExampleClinicSeeder::class);

    $this->assertDatabaseHas('users', [
        'email' => 'sarah.johnson@vetcare.test',
        'role' => 'Veterinarian',
    ]);

    $this->assertDatabaseHas('pets', [
        'pet_name' => 'Max',
        'species' => 'Dog',
    ]);

    $this->assertDatabaseHas('medical_records', [
        'diagnosis' => 'Seasonal allergic dermatitis',
    ]);

    $this->assertDatabaseHas('e_prescriptions', [
        'medication_name' => 'Cetirizine',
        'duration' => '14 days',
    ]);
});

test('medical records without prescription seeder creates records with no linked e-prescriptions', function () {
    $this->seed(MedicalRecordsWithoutPrescriptionSeeder::class);

    $record = MedicalRecord::where('diagnosis', 'Routine dental prophylaxis candidate')->first();

    expect($record)->not->toBeNull();

    $this->assertDatabaseHas('medical_records', [
        'diagnosis' => 'Resolved mild gastritis',
    ]);

    $this->assertDatabaseMissing('e_prescriptions', [
        'record_id' => $record->record_id,
    ]);
});
