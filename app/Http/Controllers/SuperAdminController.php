<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Pet;
use App\Models\SystemActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    private function roleOptions(): array
    {
        return [
            'Pet Owner' => 'Pet Owner',
            'Veterinarian' => 'Veterinarian',
            'Staff' => 'Staff',
            'Admin' => 'Admin',
        ];
    }

    private function systemSettings(): array
    {
        $defaults = config('super-admin.settings', []);

        if (!Schema::hasTable('system_settings')) {
            return $defaults;
        }

        return [
            'clinic_name' => SystemSetting::getValue('clinic_name', $defaults['clinic_name'] ?? 'VetCare Clinic'),
            'clinic_email' => SystemSetting::getValue('clinic_email', $defaults['clinic_email'] ?? null),
            'timezone' => SystemSetting::getValue('timezone', $defaults['timezone'] ?? config('app.timezone')),
            'appointment_lead_time_hours' => SystemSetting::getValue('appointment_lead_time_hours', $defaults['appointment_lead_time_hours'] ?? 2),
            'online_booking_enabled' => SystemSetting::getValue('online_booking_enabled', $defaults['online_booking_enabled'] ?? true),
            'emergency_contact' => SystemSetting::getValue('emergency_contact', $defaults['emergency_contact'] ?? null),
        ];
    }

    private function logActivity(string $action, string $description, ?string $subjectType = null, ?int $subjectId = null, array $metadata = []): void
    {
        if (!Schema::hasTable('system_activities')) {
            return;
        }

        SystemActivity::log(
            auth()->id(),
            $action,
            $subjectType,
            $subjectId,
            $description,
            $metadata
        );
    }

    /**
     * Show super admin dashboard.
     */
    public function dashboard()
    {
        $this->ensureSuperAdmin();

        $stats = [
            'total_users' => User::count(),
            'owners' => User::where('role', 'Pet Owner')->count(),
            'vets' => User::where('role', 'Veterinarian')->count(),
            'staff' => User::where('role', 'Staff')->count(),
            'admins' => User::where('is_super_admin', true)->count(),
            'total_pets' => Pet::count(),
            'total_appointments' => Appointment::count(),
            'total_consultations' => Consultation::count(),
            'recent_activities' => Schema::hasTable('system_activities') ? SystemActivity::count() : 0,
        ];

        $recentActivities = Schema::hasTable('system_activities')
            ? SystemActivity::with('actor')->latest()->limit(5)->get()
            : collect();

        return view('super-admin.dashboard', [
            'stats' => $stats,
            'currentRole' => auth()->user()->impersonating_role ?? auth()->user()->role,
            'recentActivities' => $recentActivities,
            'upcomingAppointments' => Appointment::with('pet.owner')
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->limit(5)
                ->get(),
            'recentConsultations' => Consultation::with(['appointment.pet.owner', 'veterinarian'])
                ->latest('consultation_date')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Switch to a specific role dashboard.
     */
    public function switchRole(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'role' => ['required', Rule::in(['owner', 'vet', 'staff', 'admin'])],
        ]);

        auth()->user()->update([
            'impersonating_role' => $validated['role'],
        ]);

        $this->logActivity(
            'switch-role',
            'Switched dashboard view to ' . Str::headline($validated['role']),
            User::class,
            auth()->id(),
            ['role' => $validated['role']]
        );

        return match ($validated['role']) {
            'owner' => redirect()->route('pet-owner.dashboard')->with('success', 'Switched to Pet Owner view'),
            'vet' => redirect()->route('vet.dashboard')->with('success', 'Switched to Veterinarian view'),
            'staff' => redirect()->route('staff.dashboard')->with('success', 'Switched to Staff view'),
            'admin' => redirect()->route('admin.dashboard')->with('success', 'Switched to Admin view'),
        };
    }

    /**
     * Reset to super admin view.
     */
    public function resetToSuperAdmin()
    {
        $this->ensureSuperAdmin();

        auth()->user()->update([
            'impersonating_role' => null,
        ]);

        $this->logActivity('reset-view', 'Reset dashboard view back to Super Admin');

        return redirect()->route('super-admin.dashboard')->with('success', 'Reset to Super Admin view');
    }

    /**
     * View all users and create new accounts.
     */
    public function users()
    {
        $this->ensureSuperAdmin();

        return view('super-admin.users', [
            'users' => User::orderBy('created_at', 'desc')->paginate(20),
            'roleStats' => [
                'owners' => User::where('role', 'Pet Owner')->count(),
                'vets' => User::where('role', 'Veterinarian')->count(),
                'staff' => User::where('role', 'Staff')->count(),
                'admins' => User::where('is_super_admin', true)->count(),
            ],
            'roleOptions' => $this->roleOptions(),
            'currentSettings' => $this->systemSettings(),
        ]);
    }

    /**
     * Store a new account.
     */
    public function storeUser(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_no' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys($this->roleOptions()))],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $user = User::create([
            ...$validated,
            'is_super_admin' => false,
            'impersonating_role' => null,
        ]);

        $this->logActivity('create-user', 'Created account for ' . $user->full_name, User::class, $user->user_id, [
            'role' => $user->role,
        ]);

        return redirect()->route('super-admin.users')->with('success', 'User created successfully.');
    }

    /**
     * Update an existing account.
     */
    public function updateUser(Request $request, User $user)
    {
        $this->ensureSuperAdmin();

        if ($user->isSuperAdmin() && $user->user_id === auth()->id()) {
            return back()->with('error', 'You cannot modify the currently signed-in super admin account.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'contact_no' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys($this->roleOptions()))],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'is_super_admin' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'contact_no' => $validated['contact_no'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
            'is_super_admin' => (bool) ($validated['is_super_admin'] ?? $user->is_super_admin),
        ]);

        $this->logActivity('update-user', 'Updated account for ' . $user->full_name, User::class, $user->user_id, [
            'role' => $user->role,
            'status' => $user->status,
            'is_super_admin' => $user->is_super_admin,
        ]);

        return redirect()->route('super-admin.users')->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user account.
     */
    public function deleteUser(User $user)
    {
        $this->ensureSuperAdmin();

        if ($user->user_id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'You cannot delete a super admin account.');
        }

        $userName = $user->full_name;
        $user->delete();

        $this->logActivity('delete-user', 'Deleted account for ' . $userName, User::class, $user->user_id);

        return redirect()->route('super-admin.users')->with('success', 'User deleted successfully.');
    }

    /**
     * Manage role permissions and access controls.
     */
    public function accessControl()
    {
        $this->ensureSuperAdmin();

        return view('super-admin.access-control', [
            'roles' => config('super-admin.roles', []),
            'users' => User::orderBy('full_name')->get(),
        ]);
    }

    /**
     * Update access for a specific account.
     */
    public function updateAccess(Request $request, User $user)
    {
        $this->ensureSuperAdmin();

        if ($user->user_id === auth()->id()) {
            return back()->with('error', 'You cannot change your own access level from this page.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys($this->roleOptions()))],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'is_super_admin' => ['nullable', 'boolean'],
        ]);

        $isSuperAdmin = (bool) ($validated['is_super_admin'] ?? false);

        $user->update([
            'role' => $validated['role'],
            'status' => $validated['status'],
            'is_super_admin' => $isSuperAdmin,
            'impersonating_role' => $isSuperAdmin ? $user->impersonating_role : null,
        ]);

        $this->logActivity('update-access', 'Updated access for ' . $user->full_name, User::class, $user->user_id, [
            'role' => $user->role,
            'status' => $user->status,
            'is_super_admin' => $user->is_super_admin,
        ]);

        return redirect()->route('super-admin.access-control')->with('success', 'Access permissions updated successfully.');
    }

    /**
     * Monitor appointments, consultations, and activity.
     */
    public function monitoring()
    {
        $this->ensureSuperAdmin();

        $recentActivities = Schema::hasTable('system_activities')
            ? SystemActivity::with('actor')->latest()->limit(15)->get()
            : collect();

        return view('super-admin.monitoring', [
            'appointmentStats' => [
                'pending' => Appointment::where('status', 'Pending')->count(),
                'approved' => Appointment::where('status', 'Approved')->count(),
                'completed' => Appointment::where('status', 'Completed')->count(),
                'cancelled' => Appointment::where('status', 'Cancelled')->count(),
                'missed' => Appointment::where('status', 'Missed')->count(),
            ],
            'consultationStats' => [
                'total' => Consultation::count(),
                'completed' => Consultation::where('status', 'Completed')->count(),
                'open' => Consultation::where('status', 'Open')->count(),
            ],
            'recentAppointments' => Appointment::with('pet.owner')
                ->latest('appointment_id')
                ->limit(10)
                ->get(),
            'recentConsultations' => Consultation::with(['appointment.pet.owner', 'veterinarian'])
                ->latest('consultation_date')
                ->limit(10)
                ->get(),
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Generate clinic operation reports.
     */
    public function reports(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $this->reportData($request);

        return view('super-admin.reports', $data);
    }

    /**
     * Export clinic operation reports as CSV.
     */
    public function exportReports(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $this->reportData($request);

        $this->logActivity('export-report', 'Exported clinic operations report', null, null, [
            'from' => $data['period']['from'],
            'to' => $data['period']['to'],
        ]);

        $filename = 'clinic-operations-' . now()->format('Ymd-His') . '.csv';

        return Response::streamDownload(function () use ($data): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Metric', 'Value']);

            foreach ($data['summary'] as $label => $value) {
                fputcsv($output, [$label, $value]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Recent Appointments']);
            fputcsv($output, ['Date', 'Pet', 'Owner', 'Status']);

            foreach ($data['recentAppointments'] as $appointment) {
                fputcsv($output, [
                    optional($appointment->appointment_date)->format('Y-m-d H:i'),
                    $appointment->pet?->pet_name,
                    $appointment->pet?->owner?->full_name,
                    $appointment->status,
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Recent Consultations']);
            fputcsv($output, ['Date', 'Pet', 'Veterinarian', 'Status']);

            foreach ($data['recentConsultations'] as $consultation) {
                fputcsv($output, [
                    optional($consultation->consultation_date)->format('Y-m-d H:i'),
                    $consultation->appointment?->pet?->pet_name,
                    $consultation->veterinarian?->full_name,
                    $consultation->status,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Manage clinic-wide settings.
     */
    public function settings()
    {
        $this->ensureSuperAdmin();

        return view('super-admin.settings', [
            'settings' => $this->systemSettings(),
        ]);
    }

    /**
     * Persist clinic-wide settings.
     */
    public function updateSettings(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:255'],
            'clinic_email' => ['required', 'email', 'max:255'],
            'timezone' => ['required', 'string', 'max:100'],
            'appointment_lead_time_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'online_booking_enabled' => ['nullable', 'boolean'],
            'emergency_contact' => ['nullable', 'string', 'max:50'],
        ]);

        SystemSetting::setValue('clinic_name', $validated['clinic_name'], 'string', auth()->id());
        SystemSetting::setValue('clinic_email', $validated['clinic_email'], 'string', auth()->id());
        SystemSetting::setValue('timezone', $validated['timezone'], 'string', auth()->id());
        SystemSetting::setValue('appointment_lead_time_hours', $validated['appointment_lead_time_hours'], 'integer', auth()->id());
        SystemSetting::setValue('online_booking_enabled', (bool) ($validated['online_booking_enabled'] ?? false), 'boolean', auth()->id());
        SystemSetting::setValue('emergency_contact', $validated['emergency_contact'] ?? '', 'string', auth()->id());

        $this->logActivity('update-settings', 'Updated system settings', null, null, Arr::only($validated, [
            'clinic_name',
            'clinic_email',
            'timezone',
            'appointment_lead_time_hours',
            'online_booking_enabled',
        ]));

        return redirect()->route('super-admin.settings')->with('success', 'System settings updated successfully.');
    }

    /**
     * View system analytics.
     */
    public function analytics()
    {
        $this->ensureSuperAdmin();

        $recentActivities = Schema::hasTable('system_activities')
            ? SystemActivity::with('actor')->latest()->limit(10)->get()
            : collect();

        return view('super-admin.analytics', [
            'users_by_role' => User::selectRaw('role, count(*) as count')->groupBy('role')->orderBy('role')->get(),
            'appointments_by_status' => Appointment::selectRaw('status, count(*) as count')->groupBy('status')->orderBy('status')->get(),
            'recent_users' => User::orderBy('created_at', 'desc')->limit(10)->get(),
            'recent_activities' => $recentActivities,
        ]);
    }

    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = !empty($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->startOfMonth();
        $to = !empty($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        $appointmentQuery = Appointment::query()->whereBetween('created_at', [$from, $to]);
        $consultationQuery = Consultation::query()->whereBetween('created_at', [$from, $to]);
        $hasSystemActivities = Schema::hasTable('system_activities');

        $summary = [
            'Appointments Logged' => (clone $appointmentQuery)->count(),
            'Appointments Pending' => (clone $appointmentQuery)->where('status', 'Pending')->count(),
            'Appointments Approved' => (clone $appointmentQuery)->where('status', 'Approved')->count(),
            'Consultations Completed' => (clone $consultationQuery)->where('status', 'Completed')->count(),
            'Consultations Open' => (clone $consultationQuery)->where('status', 'Open')->count(),
            'Active Client Accounts' => User::where('role', 'Pet Owner')->where('status', 'Active')->count(),
            'Active Clinic Staff' => User::where('role', 'Staff')->where('status', 'Active')->count(),
            'System Activities' => $hasSystemActivities
                ? SystemActivity::whereBetween('created_at', [$from, $to])->count()
                : 0,
        ];

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => $summary,
            'recentAppointments' => Appointment::with('pet.owner')
                ->whereBetween('created_at', [$from, $to])
                ->latest('appointment_id')
                ->limit(10)
                ->get(),
            'recentConsultations' => Consultation::with(['appointment.pet.owner', 'veterinarian'])
                ->whereBetween('created_at', [$from, $to])
                ->latest('consultation_date')
                ->limit(10)
                ->get(),
        ];
    }
}
