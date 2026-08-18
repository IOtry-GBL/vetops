<?php

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeVeterinarianDashboardFixture(): array
{
    $vet = User::factory()->create([
        'role' => 'Veterinarian',
        'full_name' => 'Dr. Ada Vet',
    ]);

    $owner = User::factory()->create([
        'role' => 'Pet Owner',
        'full_name' => 'Mina Owner',
    ]);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Luna',
    ]);

    $appointment = Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => now()->toDateString(),
        'appointment_time' => '10:30:00',
        'status' => 'Approved',
    ]);

    $consultation = Consultation::create([
        'appointment_id' => $appointment->appointment_id,
        'veterinarian_id' => $vet->user_id,
        'chief_complaint' => 'Skin irritation',
        'ai_guidance_summary' => null,
        'consultation_notes' => 'Patient is responsive and stable.',
        'consultation_date' => now(),
        'status' => 'Completed',
    ]);

    $medicalRecord = MedicalRecord::create([
        'pet_id' => $pet->pet_id,
        'consultation_id' => $consultation->consultation_id,
        'diagnosis' => 'Allergic dermatitis',
        'treatment_plan' => 'Provide medication and monitor for improvement.',
        'vaccination_notes' => 'Vaccines current.',
        'follow_up_date' => now()->addWeek()->toDateString(),
    ]);

    EPrescription::create([
        'record_id' => $medicalRecord->record_id,
        'medication_name' => 'Cetirizine',
        'dosage' => '10 mg',
        'frequency' => 'Once daily',
        'duration' => '7 days',
        'issued_at' => now(),
    ]);

    return compact('vet', 'owner', 'pet', 'appointment', 'consultation', 'medicalRecord');
}

test('veterinarian dashboard loads with schedule and adherence metrics', function () {
    $fixture = makeVeterinarianDashboardFixture();

    $response = $this->actingAs($fixture['vet'])
        ->get(route('vet.dashboard'));

    $response->assertOk()
        ->assertViewIs('veterinarian.dashboard')
        ->assertSee('Welcome, Dr. Ada Vet!')
        ->assertSee("Today's Appointments")
        ->assertSee('Medication Adherence')
        ->assertSee('Quick Links')
        ->assertSee('Upcoming Schedule')
        ->assertSee('Recent Consultations')
        ->assertSee('Recent Prescriptions');
});

test('veterinarian appointments page loads schedule summary and pending queue', function () {
    $fixture = makeVeterinarianDashboardFixture();

    $response = $this->actingAs($fixture['vet'])
        ->get(route('vet.appointments'));

    $response->assertOk()
        ->assertViewIs('veterinarian.appointments')
        ->assertSee('Appointments')
        ->assertSee('Today')
        ->assertSee('Upcoming')
        ->assertSee('Pending Requests')
        ->assertSee($fixture['pet']->name);
});
