<?php

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeVeterinarianSessionFixture(array $overrides = []): array
{
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
        'appointment_date' => $overrides['appointment_date'] ?? now()->addDay()->toDateString(),
        'appointment_time' => $overrides['appointment_time'] ?? '10:30',
        'consultation_mode' => $overrides['consultation_mode'] ?? 'In-clinic',
        'reason_for_visit' => $overrides['reason_for_visit'] ?? 'Vomiting for two days',
        'status' => $overrides['status'] ?? 'Approved',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => $overrides['chief_complaint'] ?? 'Loss of appetite',
        'ai_guidance_summary' => null,
        'consultation_notes' => $overrides['consultation_notes'] ?? null,
        'consultation_date' => $overrides['consultation_date'] ?? now()->addDay(),
        'status' => $overrides['consultation_status'] ?? 'Open',
    ]);

    return compact('vet', 'owner', 'pet', 'appointment', 'consultation');
}

test('assigned veterinarian can open the appointment session workspace', function () {
    $fixture = makeVeterinarianSessionFixture([
        'reason_for_visit' => 'Persistent scratching',
        'chief_complaint' => 'Skin redness near the neck',
    ]);

    $response = $this->actingAs($fixture['vet'])
        ->get(route('vet.appointments.session', $fixture['appointment']));

    $response->assertOk()
        ->assertViewIs('veterinarian.appointment-session')
        ->assertSee('Persistent scratching')
        ->assertSee('Skin redness near the neck')
        ->assertViewHas('appointment', function (Appointment $appointment) use ($fixture) {
            return $appointment->appointment_id === $fixture['appointment']->appointment_id;
        });

    $this->assertDatabaseHas('consultations', [
        'consultation_id' => $fixture['consultation']->consultation_id,
        'status' => 'In Progress',
    ]);
});

test('assigned veterinarian can complete an appointment session and save the record', function () {
    $fixture = makeVeterinarianSessionFixture();

    Carbon::setTestNow(Carbon::create(2026, 4, 19, 0, 30, 0, 'UTC'));

    $response = $this->actingAs($fixture['vet'])
        ->post(route('vet.appointments.session.store', $fixture['appointment']), [
            'chief_complaint' => 'Vomiting and poor appetite',
            'consultation_notes' => 'Hydration was started and the patient remained responsive.',
            'diagnosis' => 'Acute gastritis',
            'treatment_plan' => 'Provide antiemetic support and feed bland meals for three days.',
            'vaccination_notes' => 'Rabies vaccine remains current.',
            'follow_up_date' => now()->addWeek()->toDateString(),
            'prescriptions' => [
                [
                    'medication_name' => 'Ondansetron',
                    'dosage' => '4 mg',
                    'frequency' => '3',
                    'duration' => '2',
                ],
            ],
        ]);

    $response->assertRedirect(route('vet.appointments.session', $fixture['appointment']));

    $this->assertDatabaseHas('appointments', [
        'appointment_id' => $fixture['appointment']->appointment_id,
        'status' => 'Completed',
    ]);

    $this->assertDatabaseHas('consultations', [
        'consultation_id' => $fixture['consultation']->consultation_id,
        'status' => 'Completed',
        'chief_complaint' => 'Vomiting and poor appetite',
    ]);

    $record = MedicalRecord::where('consultation_id', $fixture['consultation']->consultation_id)->first();

    expect($record)->not->toBeNull();

    $this->assertDatabaseHas('medical_records', [
        'consultation_id' => $fixture['consultation']->consultation_id,
        'diagnosis' => 'Acute gastritis',
        'treatment_plan' => 'Provide antiemetic support and feed bland meals for three days.',
    ]);

    $this->assertDatabaseHas('e_prescriptions', [
        'record_id' => $record->record_id,
        'medication_name' => 'Ondansetron',
        'dosage' => '4 mg',
        'frequency' => '3 times daily',
        'duration' => '2 days',
    ]);

    $prescription = EPrescription::where('record_id', $record->record_id)
        ->where('medication_name', 'Ondansetron')
        ->firstOrFail();

    expect($prescription->adherenceLogs()->count())->toBe(6);

    $firstDose = $prescription->adherenceLogs()->orderBy('scheduled_datetime')->firstOrFail();
    expect($firstDose->scheduled_datetime->timezone('Asia/Manila')->format('Y-m-d H:i'))->toBe('2026-04-19 12:00');
    expect($firstDose->confirmation_deadline->timezone('Asia/Manila')->format('Y-m-d H:i'))->toBe('2026-04-19 15:00');

    Carbon::setTestNow();
});

test('assigned veterinarian can add multiple prescriptions from one appointment session', function () {
    $fixture = makeVeterinarianSessionFixture();

    $response = $this->actingAs($fixture['vet'])
        ->post(route('vet.appointments.session.store', $fixture['appointment']), [
            'chief_complaint' => 'Vomiting and poor appetite',
            'consultation_notes' => 'Hydration was started and the patient remained responsive.',
            'diagnosis' => 'Acute gastritis',
            'treatment_plan' => 'Provide antiemetic support and feed bland meals for three days.',
            'vaccination_notes' => 'Rabies vaccine remains current.',
            'follow_up_date' => now()->addWeek()->toDateString(),
            'prescriptions' => [
                [
                    'medication_name' => 'Ondansetron',
                    'dosage' => '4 mg',
                    'frequency' => 'Twice daily',
                    'duration' => '3 days',
                ],
                [
                    'medication_name' => 'Probiotic Paste',
                    'dosage' => '2 mL',
                    'frequency' => 'Once daily',
                    'duration' => '5 days',
                ],
            ],
        ]);

    $response->assertRedirect(route('vet.appointments.session', $fixture['appointment']));

    $record = MedicalRecord::where('consultation_id', $fixture['consultation']->consultation_id)->first();

    expect($record)->not->toBeNull();

    $this->assertDatabaseHas('e_prescriptions', [
        'record_id' => $record->record_id,
        'medication_name' => 'Ondansetron',
    ]);

    $this->assertDatabaseHas('e_prescriptions', [
        'record_id' => $record->record_id,
        'medication_name' => 'Probiotic Paste',
    ]);
});

test('did not arrive marks the appointment as missed for the pet owner', function () {
    $fixture = makeVeterinarianSessionFixture();

    $response = $this->actingAs($fixture['vet'])
        ->post(route('vet.appointments.dna', $fixture['appointment']));

    $response->assertRedirect(route('vet.appointments'));

    $this->assertDatabaseHas('appointments', [
        'appointment_id' => $fixture['appointment']->appointment_id,
        'status' => 'Missed',
    ]);

    $this->assertDatabaseHas('consultations', [
        'consultation_id' => $fixture['consultation']->consultation_id,
        'status' => 'Missed',
    ]);

    $ownerView = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.appointments'));

    $ownerView->assertOk()
        ->assertSee('Missed')
        ->assertViewHas('missedAppointments', function ($missedAppointments) use ($fixture) {
            return $missedAppointments->pluck('appointment_id')->contains($fixture['appointment']->appointment_id);
        })
        ->assertViewHas('upcomingAppointments', function ($upcomingAppointments) use ($fixture) {
            return !$upcomingAppointments->pluck('appointment_id')->contains($fixture['appointment']->appointment_id);
        });
});

test('pet owner prescriptions page shows e-prescriptions written from appointment sessions', function () {
    $fixture = makeVeterinarianSessionFixture([
        'status' => 'Completed',
        'consultation_status' => 'Completed',
    ]);

    $record = MedicalRecord::create([
        'pet_id' => $fixture['pet']->pet_id,
        'consultation_id' => $fixture['consultation']->consultation_id,
        'diagnosis' => 'Seasonal dermatitis',
        'treatment_plan' => 'Use medicated shampoo weekly.',
        'vaccination_notes' => 'Vaccinations are up to date.',
        'follow_up_date' => now()->addDays(10)->toDateString(),
    ]);

    EPrescription::create([
        'record_id' => $record->record_id,
        'medication_name' => 'Cetirizine',
        'dosage' => '10 mg',
        'frequency' => 'Once daily',
        'duration' => '14 days',
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($fixture['owner'])
        ->get(route('pet-owner.prescriptions'));

    $response->assertOk()
        ->assertSee('Cetirizine')
        ->assertSee('Seasonal dermatitis')
        ->assertSee($fixture['pet']->name)
        ->assertViewHas('prescriptions', function ($prescriptions) {
            return $prescriptions->pluck('medication_name')->contains('Cetirizine');
        });
});
