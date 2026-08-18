<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed a default super admin account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@vetcare.test'],
            [
                'full_name' => 'System Administrator',
                'password' => Hash::make('password'),
                'contact_no' => '0917-000-1234',
                'role' => 'Staff',
                'status' => 'Active',
                'is_super_admin' => true,
                'impersonating_role' => null,
            ]
        );

        $this->command?->info('Admin account seeded: admin@vetcare.test / password');
    }
}
