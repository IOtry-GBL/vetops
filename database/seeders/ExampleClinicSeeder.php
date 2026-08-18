<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ExampleClinicSeeder extends Seeder
{
    /**
     * Seed the database with example clinic data that matches the current schema.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@vetcare.test'],
            [
                'full_name' => 'Clinic Super Admin',
                'password' => Hash::make('password'),
                'contact_no' => '0917-000-0000',
                'role' => 'Staff',
                'status' => 'Active',
                'is_super_admin' => true,
                'impersonating_role' => null,
            ]
        );

        $vetSarah = User::updateOrCreate(
            ['email' => 'sarah.johnson@vetcare.test'],
            [
                'full_name' => 'Dr. Sarah Johnson',
                'password' => Hash::make('password'),
                'contact_no' => '0917-111-0001',
                'role' => 'Veterinarian',
                'status' => 'Active',
                'is_super_admin' => false,
                'impersonating_role' => null,
            ]
        );

        $vetMichael = User::updateOrCreate(
            ['email' => 'michael.chen@vetcare.test'],
            [
                'full_name' => 'Dr. Michael Chen',
                'password' => Hash::make('password'),
                'contact_no' => '0917-111-0002',
                'role' => 'Veterinarian',
                'status' => 'Active',
                'is_super_admin' => false,
                'impersonating_role' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'frontdesk@vetcare.test'],
            [
                'full_name' => 'Nina Frontdesk',
                'password' => Hash::make('password'),
                'contact_no' => '0917-222-0001',
                'role' => 'Staff',
                'status' => 'Active',
                'is_super_admin' => false,
                'impersonating_role' => null,
            ]
        );

        $ownerJohn = User::updateOrCreate(
            ['email' => 'john.petowner@example.com'],
            [
                'full_name' => 'John Smith',
                'password' => Hash::make('password'),
                'contact_no' => '0917-333-0001',
                'role' => 'Pet Owner',
                'status' => 'Active',
                'is_super_admin' => false,
                'impersonating_role' => null,
            ]
        );

        $ownerEmma = User::updateOrCreate(
            ['email' => 'emma.petowner@example.com'],
            [
                'full_name' => 'Emma Davis',
                'password' => Hash::make('password'),
                'contact_no' => '0917-333-0002',
                'role' => 'Pet Owner',
                'status' => 'Active',
                'is_super_admin' => false,
                'impersonating_role' => null,
            ]
        );

        $max = Pet::updateOrCreate(
            ['user_id' => $ownerJohn->user_id, 'pet_name' => 'Max'],
            [
                'species' => 'Dog',
                'breed' => 'Golden Retriever',
                'date_of_birth' => '2019-06-15',
                'weight' => 28.40,
                'sex' => 'Male',
            ]
        );

        $bella = Pet::updateOrCreate(
            ['user_id' => $ownerJohn->user_id, 'pet_name' => 'Bella'],
            [
                'species' => 'Cat',
                'breed' => 'Persian',
                'date_of_birth' => '2021-02-04',
                'weight' => 4.90,
                'sex' => 'Female',
            ]
        );

        $luna = Pet::updateOrCreate(
            ['user_id' => $ownerEmma->user_id, 'pet_name' => 'Luna'],
            [
                'species' => 'Dog',
                'breed' => 'Beagle',
                'date_of_birth' => '2020-10-10',
                'weight' => 12.75,
                'sex' => 'Female',
            ]
        );

        $maxAppointment = Appointment::firstOrCreate(
            [
                'pet_id' => $max->pet_id,
                'appointment_date' => '2026-03-08',
                'appointment_time' => '09:30:00',
            ],
            [
                'consultation_mode' => 'Teleconsultation',
                'reason_for_visit' => 'Persistent itching, redness, and paw licking.',
                'status' => 'Completed',
                'proof_of_payment' => null,
            ]
        );

        $bellaAppointment = Appointment::firstOrCreate(
            [
                'pet_id' => $bella->pet_id,
                'appointment_date' => '2026-03-11',
                'appointment_time' => '11:15:00',
            ],
            [
                'consultation_mode' => 'In-clinic',
                'reason_for_visit' => 'Appetite drop and mild lethargy over the last two days.',
                'status' => 'Completed',
                'proof_of_payment' => null,
            ]
        );

        $lunaAppointment = Appointment::firstOrCreate(
            [
                'pet_id' => $luna->pet_id,
                'appointment_date' => '2026-03-18',
                'appointment_time' => '15:00:00',
            ],
            [
                'consultation_mode' => 'Teleconsultation',
                'reason_for_visit' => 'Follow-up after ear infection treatment.',
                'status' => 'Approved',
                'proof_of_payment' => null,
            ]
        );

        $maxConsultation = Consultation::firstOrCreate(
            ['appointment_id' => $maxAppointment->appointment_id],
            [
                'veterinarian_id' => $vetSarah->user_id,
                'chief_complaint' => 'Generalized itching with redness around the paws and abdomen.',
                'ai_guidance_summary' => 'Possible allergic dermatitis; review diet and skin exposure triggers.',
                'consultation_notes' => 'Recommended antihistamine therapy and skin barrier support.',
                'consultation_date' => Carbon::create(2026, 3, 8, 9, 30),
                'status' => 'Completed',
            ]
        );

        $bellaConsultation = Consultation::firstOrCreate(
            ['appointment_id' => $bellaAppointment->appointment_id],
            [
                'veterinarian_id' => $vetMichael->user_id,
                'chief_complaint' => 'Reduced appetite and mild weakness.',
                'ai_guidance_summary' => 'Monitor hydration and complete blood chemistry if symptoms persist.',
                'consultation_notes' => 'Improvement expected with diet support and close observation.',
                'consultation_date' => Carbon::create(2026, 3, 11, 11, 15),
                'status' => 'Completed',
            ]
        );

        $lunaConsultation = Consultation::firstOrCreate(
            ['appointment_id' => $lunaAppointment->appointment_id],
            [
                'veterinarian_id' => $vetSarah->user_id,
                'chief_complaint' => 'Recheck after treatment for otitis externa.',
                'ai_guidance_summary' => 'Symptoms are improving; continue cleaning and medication until follow-up.',
                'consultation_notes' => 'Ear canal is less inflamed; continue current regimen.',
                'consultation_date' => Carbon::create(2026, 3, 18, 15, 0),
                'status' => 'Follow-up',
            ]
        );

        $maxRecord = MedicalRecord::updateOrCreate(
            ['consultation_id' => $maxConsultation->consultation_id],
            [
                'pet_id' => $max->pet_id,
                'diagnosis' => 'Seasonal allergic dermatitis',
                'treatment_plan' => 'Begin cetirizine once daily, start omega-3 supplementation, and use hypoallergenic shampoo twice weekly.',
                'vaccination_notes' => 'Core vaccinations current as of January 2026.',
                'follow_up_date' => '2026-03-22',
            ]
        );

        $bellaRecord = MedicalRecord::updateOrCreate(
            ['consultation_id' => $bellaConsultation->consultation_id],
            [
                'pet_id' => $bella->pet_id,
                'diagnosis' => 'Mild gastritis',
                'treatment_plan' => 'Offer small frequent meals, maintain hydration, and monitor appetite for 72 hours.',
                'vaccination_notes' => 'Rabies booster due in July 2026.',
                'follow_up_date' => '2026-03-25',
            ]
        );

        $lunaRecord = MedicalRecord::updateOrCreate(
            ['consultation_id' => $lunaConsultation->consultation_id],
            [
                'pet_id' => $luna->pet_id,
                'diagnosis' => 'Resolving otitis externa',
                'treatment_plan' => 'Continue ear flush once daily and finish the remaining anti-inflammatory drops.',
                'vaccination_notes' => 'No vaccination concerns recorded during follow-up.',
                'follow_up_date' => '2026-03-29',
            ]
        );

        EPrescription::updateOrCreate(
            ['record_id' => $maxRecord->record_id, 'medication_name' => 'Cetirizine'],
            [
                'dosage' => '10 mg',
                'frequency' => 'Once daily',
                'duration' => '14 days',
                'issued_at' => Carbon::create(2026, 3, 8, 9, 45),
            ]
        );

        EPrescription::updateOrCreate(
            ['record_id' => $maxRecord->record_id, 'medication_name' => 'Omega-3 Fish Oil'],
            [
                'dosage' => '1 capsule',
                'frequency' => 'Once daily',
                'duration' => '30 days',
                'issued_at' => Carbon::create(2026, 3, 8, 9, 50),
            ]
        );

        EPrescription::updateOrCreate(
            ['record_id' => $bellaRecord->record_id, 'medication_name' => 'Probiotic Paste'],
            [
                'dosage' => '2 mL',
                'frequency' => 'Twice daily',
                'duration' => '5 days',
                'issued_at' => Carbon::create(2026, 3, 11, 11, 35),
            ]
        );

        EPrescription::updateOrCreate(
            ['record_id' => $lunaRecord->record_id, 'medication_name' => 'Otic Anti-inflammatory Drops'],
            [
                'dosage' => '4 drops',
                'frequency' => 'Twice daily',
                'duration' => '7 days',
                'issued_at' => Carbon::create(2026, 3, 18, 15, 20),
            ]
        );
    }
}
