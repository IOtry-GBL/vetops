<?php

return [
    'roles' => [
        'Pet Owner' => [
            'label' => 'Pet Owner',
            'permissions' => ['pets', 'appointments', 'prescriptions', 'notifications'],
        ],
        'Veterinarian' => [
            'label' => 'Veterinarian',
            'permissions' => ['appointments', 'consultations', 'medical-records', 'prescriptions', 'adherence'],
        ],
        'Staff' => [
            'label' => 'Clinic Staff',
            'permissions' => ['clients', 'pets', 'appointments', 'medical-records', 'reports'],
        ],
        'Admin' => [
            'label' => 'Admin',
            'permissions' => ['users', 'roles', 'monitoring', 'reports', 'settings'],
        ],
        'Super Admin' => [
            'label' => 'Super Admin',
            'permissions' => ['all'],
        ],
    ],

    'settings' => [
        'clinic_name' => 'VetCare Clinic',
        'clinic_email' => 'clinic@vetcare.test',
        'timezone' => 'Asia/Manila',
        'appointment_lead_time_hours' => 2,
        'online_booking_enabled' => true,
        'emergency_contact' => '0917-000-1234',
    ],
];
