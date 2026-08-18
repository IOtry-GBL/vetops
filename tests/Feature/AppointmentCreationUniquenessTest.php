<?php

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeActiveAppointmentFixture(string $role): array
{
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $owner = $role === 'Pet Owner'
        ? $user
        : User::factory()->create(['role' => 'Pet Owner']);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Peanut',
        'species' => 'Dog',
        'breed' => 'Mixed Breed',
    ]);

    Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => now()->addDay()->toDateString(),
        'appointment_time' => '10:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Existing active visit',
        'status' => 'Pending',
    ]);

    return compact('user', 'owner', 'pet');
}

test('pet owners cannot create another appointment for a pet with an active appointment', function () {
    $fixture = makeActiveAppointmentFixture('Pet Owner');

    $response = $this->actingAs($fixture['user'])
        ->from(route('pet-owner.appointments.book'))
        ->post(route('pet-owner.appointments.store'), [
            'pet_id' => $fixture['pet']->pet_id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'reason' => 'Follow-up visit',
        ]);

    $response->assertRedirect(route('pet-owner.appointments.book'))
        ->assertSessionHasErrors('pet_id');

    expect(Appointment::count())->toBe(1);
});

test('staff cannot create another appointment for a pet with an active appointment', function () {
    $fixture = makeActiveAppointmentFixture('Staff');
    $vet = User::factory()->create(['role' => 'Veterinarian']);

    $response = $this->actingAs($fixture['user'])
        ->from(route('staff.appointments.create'))
        ->post(route('staff.appointments.store'), [
            'pet_id' => $fixture['pet']->pet_id,
            'vet_id' => $vet->user_id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'reason' => 'Second scheduled visit',
            'status' => 'approved',
        ]);

    $response->assertRedirect(route('staff.appointments.create'))
        ->assertSessionHasErrors('pet_id');

    expect(Appointment::count())->toBe(1);
});

test('veterinarians cannot create another appointment for a pet with an active appointment', function () {
    $fixture = makeActiveAppointmentFixture('Veterinarian');

    $response = $this->actingAs($fixture['user'])
        ->from(route('vet.appointments.create'))
        ->post(route('vet.appointments.store'), [
            'pet_id' => $fixture['pet']->pet_id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'reason' => 'Second scheduled visit',
            'status' => 'approved',
        ]);

    $response->assertRedirect(route('vet.appointments.create'))
        ->assertSessionHasErrors('pet_id');

    expect(Appointment::count())->toBe(1);
});

test('veterinarians can create appointment when previous active-status appointment is already stale', function () {
    $user = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $owner = User::factory()->create(['role' => 'Pet Owner']);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Peanut',
        'species' => 'Dog',
        'breed' => 'Mixed Breed',
    ]);

    Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => now()->subDay()->toDateString(),
        'appointment_time' => '10:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Old unfinished visit',
        'status' => 'Pending',
    ]);

    $response = $this->actingAs($user)
        ->from(route('vet.appointments.create'))
        ->post(route('vet.appointments.store'), [
            'pet_id' => $pet->pet_id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'reason' => 'New scheduled visit',
            'status' => 'approved',
        ]);

    $response->assertRedirect(route('vet.appointments'));

    expect(Appointment::count())->toBe(2);
});

test('veterinarians can create appointment when previous active-status appointment time already passed today', function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 16, 14, 0, 0, 'Asia/Manila'));

    $user = User::factory()->create([
        'role' => 'Veterinarian',
    ]);

    $owner = User::factory()->create(['role' => 'Pet Owner']);

    $pet = Pet::factory()->create([
        'user_id' => $owner->user_id,
        'pet_name' => 'Peanut',
        'species' => 'Dog',
        'breed' => 'Mixed Breed',
    ]);

    Appointment::factory()->create([
        'pet_id' => $pet->pet_id,
        'appointment_date' => '2026-05-16',
        'appointment_time' => '10:00',
        'consultation_mode' => 'In-clinic',
        'reason_for_visit' => 'Earlier missed visit',
        'status' => 'Pending',
    ]);

    $response = $this->actingAs($user)
        ->from(route('vet.appointments.create'))
        ->post(route('vet.appointments.store'), [
            'pet_id' => $pet->pet_id,
            'appointment_date' => '2026-05-18T09:00',
            'reason' => 'New scheduled visit',
            'status' => 'approved',
        ]);

    $response->assertRedirect(route('vet.appointments'));

    expect(Appointment::count())->toBe(2);

    Carbon::setTestNow();
});
