<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeSuperAdminManagementFixture(): User
{
    return User::create([
        'full_name' => 'System Root',
        'email' => 'root@vetcare.test',
        'password' => Hash::make('password'),
        'contact_no' => '0917-000-0000',
        'role' => 'Staff',
        'status' => 'Active',
        'is_super_admin' => true,
    ]);
}

test('super admin access control page loads', function () {
    $superAdmin = makeSuperAdminManagementFixture();

    $this->actingAs($superAdmin)
        ->get(route('super-admin.access-control'))
        ->assertOk()
        ->assertSee('Roles & Access')
        ->assertSee('User Access Overrides');
});

test('super admin monitoring page loads', function () {
    $superAdmin = makeSuperAdminManagementFixture();

    $this->actingAs($superAdmin)
        ->get(route('super-admin.monitoring'))
        ->assertOk()
        ->assertSee('Monitoring')
        ->assertSee('Recent Activity');
});

test('super admin reports page loads', function () {
    $superAdmin = makeSuperAdminManagementFixture();

    $this->actingAs($superAdmin)
        ->get(route('super-admin.reports'))
        ->assertOk()
        ->assertSee('Clinic Operation Reports')
        ->assertSee('Export CSV');
});

test('super admin settings page can update system settings', function () {
    $superAdmin = makeSuperAdminManagementFixture();

    $response = $this->actingAs($superAdmin)->put(route('super-admin.settings.update'), [
        'clinic_name' => 'VetCare Central',
        'clinic_email' => 'admin@vetcare.test',
        'timezone' => 'Asia/Manila',
        'appointment_lead_time_hours' => 4,
        'online_booking_enabled' => true,
        'emergency_contact' => '0917-111-2222',
    ]);

    $response->assertRedirect(route('super-admin.settings'));

    expect(SystemSetting::getValue('clinic_name'))->toBe('VetCare Central');
    expect(SystemSetting::getValue('appointment_lead_time_hours'))->toBe(4);
    expect(SystemSetting::getValue('online_booking_enabled'))->toBeTrue();
});

test('super admin can create a client account', function () {
    $superAdmin = makeSuperAdminManagementFixture();

    $this->actingAs($superAdmin)
        ->post(route('super-admin.users.store'), [
            'full_name' => 'Jamie Owner',
            'email' => 'jamie@example.test',
            'contact_no' => '0917-222-3333',
            'password' => 'password123',
            'role' => 'Pet Owner',
            'status' => 'Active',
        ])
        ->assertRedirect(route('super-admin.users'));

    $this->assertDatabaseHas('users', [
        'email' => 'jamie@example.test',
        'role' => 'Pet Owner',
        'status' => 'Active',
    ]);
});
