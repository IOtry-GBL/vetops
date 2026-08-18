<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Pet;
use App\Models\User;
use App\Models\MedicalRecord;
use App\Models\EPrescription;
use App\Models\AdherenceLog;
use App\Models\AdherenceNotification;
use App\Models\AppointmentNotification;
use App\Models\StaffNotificationDismissal;
use App\Services\AppointmentScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    /**
     * Display the staff dashboard
     */
    public function dashboard()
    {
        $clinicNow = now('Asia/Manila');
        $clinicToday = $clinicNow->toDateString();

        $allAppointments = Appointment::count();
        $todayAppointments = Appointment::whereDate('appointment_date', $clinicToday)
            ->where('status', 'Approved')
            ->count();
        $pending = Appointment::where('status', 'Pending')->count();
        
        // Get appointment requests awaiting confirmation
        $pendingRequests = Appointment::where('status', 'Pending')
            ->where('appointment_date', '>=', $clinicToday)
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get upcoming approved appointments
        $upcomingApprovedAppointments = Appointment::where('status', 'Approved')
            ->where('appointment_date', '>=', $clinicToday)
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get today's appointments
        $todayListAppointments = Appointment::whereDate('appointment_date', $clinicToday)
            ->where('status', 'Approved')
            ->with('pet.owner')
            ->orderBy('appointment_time', 'asc')
            ->get();
        
        // Get regular check-ups (appointments not related to emergencies)
        $regularAppointments = Appointment::where('status', 'Approved')
            ->where('appointment_date', '>=', $clinicToday)
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get missed approved appointments (past appointments that are still approved)
        $missedApprovedAppointments = Appointment::where(function ($query) {
            $query->where('status', 'Missed')
                ->orWhere(function ($approvedQuery) {
                    $approvedQuery->where('status', 'Approved')
                        ->where('appointment_date', '<', now('Asia/Manila')->toDateString());
                });
        })
            ->with('pet.owner')
            ->orderBy('appointment_date', 'desc')
            ->get();

        // Get recent pending appointments for activity summary
        $recentPending = Appointment::where('status', 'Pending')
            ->where('appointment_date', '>=', $clinicToday)
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->limit(5)
            ->get();



        return view('staff.dashboard', compact('allAppointments', 'todayAppointments', 'pending', 'pendingRequests', 'upcomingApprovedAppointments', 'todayListAppointments', 'regularAppointments', 'missedApprovedAppointments', 'recentPending'));
    }

    /**
     * Display appointment queue
     */
    public function queue()
    {
        return view('staff.queue', $this->pendingAppointmentQueueData());
    }

    /**
     * Display notifications
     */
    public function notifications()
    {
        return view('staff.notifications', $this->staffNotificationData());
    }

    /**
     * Return live notification fragments for the staff feed
     */
    public function notificationsFeed()
    {
        $data = $this->staffNotificationData();

        return response()->json([
            'summary_html' => view('staff.partials.notification-summary', $data)->render(),
            'feed_html' => view('staff.partials.notification-feed', $data)->render(),
            'synced_at' => $data['lastSyncedAt']->format('M j, Y g:i:s A') . ' PH',
        ]);
    }

    /**
     * Hide one notification from the signed-in staff member's feed.
     */
    public function deleteNotification(string $type, int $id)
    {
        $allowedTypes = ['new-appointment', 'cancelled-appointment', 'did-not-arrive', 'adherence'];

        abort_unless(in_array($type, $allowedTypes, true), 404);

        if ($type === 'adherence') {
            AdherenceNotification::findOrFail($id);
        } else {
            Appointment::findOrFail($id);
        }

        StaffNotificationDismissal::firstOrCreate([
            'user_id' => auth()->id(),
            'notification_key' => $this->staffNotificationKey($type, $id),
        ]);

        return redirect()
            ->route('staff.notifications')
            ->with('success', 'Notification deleted from your feed.');
    }

    /**
     * Clear all notifications for the current staff member
     */
    public function clearAllNotifications()
    {
        $user = auth()->user();
        $manilaNow = now('Asia/Manila');
        $recentWindowStartUtc = $manilaNow->copy()->subHours(24)->utc();
        $todayStartUtc = $manilaNow->copy()->startOfDay()->utc();
        $todayEndUtc = $manilaNow->copy()->endOfDay()->utc();
        
        // Get all current notifications without filtering by dismissed keys
        $newAppointmentEntries = Appointment::where('created_at', '>=', $recentWindowStartUtc)
            ->limit(12)
            ->pluck('appointment_id');
        
        $cancelledTodayAppointments = Appointment::where('status', 'Cancelled')
            ->whereBetween('updated_at', [$todayStartUtc, $todayEndUtc])
            ->limit(12)
            ->pluck('appointment_id');
        
        $recentDidNotArriveAppointments = Appointment::where('status', 'Missed')
            ->where('updated_at', '>=', $recentWindowStartUtc)
            ->limit(12)
            ->pluck('appointment_id');
        
        $adherenceNotifications = AdherenceNotification::where('status', 'Confirmed')
            ->whereNotNull('confirmed_at')
            ->where(function ($query) use ($recentWindowStartUtc) {
                $query->where('confirmed_at', '>=', $recentWindowStartUtc)
                    ->orWhere('scheduled_at', '>=', $recentWindowStartUtc);
            })
            ->limit(12)
            ->pluck('notification_id');
        
        // Create dismissals for all notification types
        foreach ($newAppointmentEntries as $id) {
            StaffNotificationDismissal::firstOrCreate([
                'user_id' => $user->id,
                'notification_key' => $this->staffNotificationKey('new-appointment', $id),
            ]);
        }
        
        foreach ($cancelledTodayAppointments as $id) {
            StaffNotificationDismissal::firstOrCreate([
                'user_id' => $user->id,
                'notification_key' => $this->staffNotificationKey('cancelled-appointment', $id),
            ]);
        }
        
        foreach ($recentDidNotArriveAppointments as $id) {
            StaffNotificationDismissal::firstOrCreate([
                'user_id' => $user->id,
                'notification_key' => $this->staffNotificationKey('did-not-arrive', $id),
            ]);
        }
        
        foreach ($adherenceNotifications as $id) {
            StaffNotificationDismissal::firstOrCreate([
                'user_id' => $user->id,
                'notification_key' => $this->staffNotificationKey('adherence', $id),
            ]);
        }
        
        return response()->json(['message' => 'All notifications cleared successfully'], 200);
    }

    /**
     * Show pending appointment requests from pet owners
     */
    public function pendingAppointments()
    {
        return view('staff.queue', $this->pendingAppointmentQueueData());
    }

    /**
     * Show confirmation/adjustment form for appointment
     */
    public function confirmAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);
        $vets = User::where('role', 'Veterinarian')->get();
        $calendarBookings = AppointmentScheduleService::calendarBookings();
        $calendarSlots = AppointmentScheduleService::WORKING_HOURS;

        return view('staff.confirm-appointment', compact('appointment', 'vets', 'calendarBookings', 'calendarSlots'));
    }

    /**
     * Show create appointment form
     */
    public function createAppointment()
    {
        $pets = Pet::with('owner')->get();
        $vets = User::where('role', 'Veterinarian')->get();
        $unavailablePetIds = Appointment::query()->activeEntries()->pluck('pet_id')->all();
        $calendarBookings = AppointmentScheduleService::calendarBookings();
        $calendarSlots = AppointmentScheduleService::WORKING_HOURS;

        return view('staff.create-appointment', compact('pets', 'vets', 'unavailablePetIds', 'calendarBookings', 'calendarSlots'));
    }

    /**
     * Store new appointment
     */
    public function storeAppointment(Request $request)
    {
        AppointmentScheduleService::normalizeDateTimeInput($request);

        if (!$request->filled('consultation_mode')) {
            $request->merge(['consultation_mode' => 'In-clinic']);
        }

        $reason = $request->input('reason_for_visit') ?? $request->input('reason');
        $request->merge(['reason' => $reason]);

        $statusInput = $request->input('status');
        if ($statusInput) {
            $statusMap = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rescheduled' => 'Rescheduled',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ];
            $normalized = strtolower($statusInput);
            $request->merge(['status' => $statusMap[$normalized] ?? $statusInput]);
        } else {
            $request->merge(['status' => 'Pending']);
        }

        $validator = Validator::make($request->all(), [
            'pet_id' => 'required|exists:pets,pet_id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'consultation_mode' => 'required|in:In-clinic,Teleconsultation',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:Pending,Approved,Rescheduled,Completed,Cancelled',
            'vet_id' => 'nullable|exists:users,user_id',
        ]);
        $validator->after(fn ($validator) => AppointmentScheduleService::validateSlot($validator, $request));
        $validated = $validator->validate();

        if (Appointment::petHasActiveEntry((int) $validated['pet_id'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'pet_id' => 'This pet already has an active appointment entry. Complete, cancel, or mark the existing appointment first.',
                ]);
        }

        $appointment = Appointment::create([
            'pet_id' => $validated['pet_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'consultation_mode' => $validated['consultation_mode'],
            'reason_for_visit' => $reason,
            'status' => $validated['status'],
        ]);

        // Create notification with the appointment status
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, $validated['status']);
        } catch (\Exception $e) {
            \Log::error('Error creating appointment notification', ['error' => $e->getMessage()]);
        }

        if (!empty($validated['vet_id'])) {
            $consultationDate = Carbon::createFromFormat(
                'Y-m-d H:i',
                $validated['appointment_date'] . ' ' . $validated['appointment_time']
            );

            Consultation::create([
                'appointment_id' => $appointment->appointment_id,
                'veterinarian_id' => $validated['vet_id'],
                'chief_complaint' => $appointment->reason_for_visit ?? 'Appointment scheduled',
                'consultation_date' => $consultationDate,
                'status' => 'Open',
            ]);
        }

        return redirect()->route('staff.appointments.pending')->with('success', 'Appointment created successfully!');
    }

    /**
     * Approve and finalize appointment
     */
    public function approveAppointment(Request $request, $id)
    {
        $appointment = Appointment::with('pet.owner')->findOrFail($id);

        AppointmentScheduleService::normalizeDateTimeInput($request);

        if (!$request->filled('consultation_mode')) {
            $request->merge(['consultation_mode' => 'In-clinic']);
        }

        if ($request->filled('status')) {
            $statusMap = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rescheduled' => 'Rescheduled',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ];
            $normalized = strtolower($request->input('status'));
            $request->merge(['status' => $statusMap[$normalized] ?? $request->input('status')]);
        }

        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'consultation_mode' => 'required|in:In-clinic,Teleconsultation',
            
            'status' => 'required|in:Pending,Approved,Rescheduled,Completed,Cancelled',
            'vet_id' => 'nullable|exists:users,user_id',
        ]);
        $validator->after(fn ($validator) => AppointmentScheduleService::validateSlot($validator, $request, $appointment->appointment_id));
        $validated = $validator->validate();

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'consultation_mode' => $validated['consultation_mode'],
            'status' => $validated['status'],
        ]);

        // Record the latest notification row for this appointment/status pair.
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, $validated['status']);
            \Log::info('Appointment notification created', [
                'appointment_id' => $appointment->appointment_id,
                'status' => $validated['status'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating appointment notification', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->appointment_id,
            ]);
        }

        if (!empty($validated['vet_id'])) {
            $consultationDate = Carbon::createFromFormat(
                'Y-m-d H:i',
                $validated['appointment_date'] . ' ' . $validated['appointment_time']
            );

            $consultation = $appointment->consultation()
                ->orderByDesc('consultation_date')
                ->first();

            $payload = [
                'appointment_id' => $appointment->appointment_id,
                'veterinarian_id' => $validated['vet_id'],
                'chief_complaint' => $appointment->reason_for_visit ?? 'Appointment confirmed',
                'consultation_date' => $consultationDate,
                'status' => 'Open',
            ];

            if ($consultation) {
                $consultation->update($payload);
            } else {
                Consultation::create($payload);
            }
        }

        return redirect()->route('staff.appointments.pending')->with('success', 'Appointment confirmed!');
    }

    /**
     * Reject a pending appointment
     */
    public function rejectAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);
        
        if ($appointment->status !== 'Pending') {
            return redirect()->back()->with('error', 'Only pending appointments can be rejected');
        }

        $appointment->update(['status' => 'Cancelled']);

        // Create notification for rejected appointment
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, 'Cancelled');
        } catch (\Exception $e) {
            \Log::error('Error creating rejected appointment notification', ['error' => $e->getMessage()]);
        }

        return redirect()->route('staff.queue')->with('success', 'Appointment rejected successfully');
    }

    /**
     * SECTION 2.2: PATIENT RECORDS MANAGEMENT
     */

    /**
     * View all patients (pets) with owner information
     */
    public function patients(Request $request)
    {
        $query = Pet::with('owner');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('pet_name', 'like', "%{$search}%")
                  ->orWhere('species', 'like', "%{$search}%")
                  ->orWhere('breed', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                      $ownerQuery->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by species
        if ($request->filled('species')) {
            $query->where('species', $request->input('species'));
        }

        $patients = $query->orderBy('pet_name')->paginate(15);
        $species = Pet::distinct()->pluck('species');

        return view('staff.patients.index', compact('patients', 'species'));
    }

    /**
     * View patient details
     */
    public function patientDetails($petId)
    {
        $pet = Pet::with([
            'owner',
            'appointments' => function ($q) {
                $q->orderBy('appointment_date', 'desc');
            },
            'medicalRecords' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
        ])->findOrFail($petId);

        // Get latest medical record
        $latestMedicalRecord = $pet->medicalRecords->first();

        // Get active prescriptions
        $activePrescriptions = $pet->medicalRecords()
            ->with('prescriptions')
            ->get()
            ->flatMap->prescriptions
            ->filter(function ($prescription) {
                return $prescription->issued_at >= now()->subDays(30);
            });

        // Get appointment history
        $appointmentHistory = $pet->appointments()->orderBy('appointment_date', 'desc')->limit(10)->get();

        return view('staff.patients.details', compact('pet', 'latestMedicalRecord', 'activePrescriptions', 'appointmentHistory'));
    }

    /**
     * Show form to register new patient
     */
    public function registerPatient()
    {
        $owners = User::where('role', 'Pet Owner')->orderBy('full_name')->get();
        $species = ['Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster', 'Guinea Pig', 'Fish', 'Other'];

        return view('staff.patients.register', compact('owners', 'species'));
    }

    /**
     * Store new patient
     */
    public function storePatient(Request $request)
    {
        $validated = $request->validate([
            'pet_name' => 'required|string|max:100',
            'user_id' => 'required|exists:users,user_id',
            'species' => 'required|string|max:50',
            'breed' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before:today',
            'weight' => 'required|numeric|min:0.1|max:200',
            'sex' => 'required|in:Male,Female,Other',
        ]);

        Pet::create($validated);

        return redirect()->route('staff.patients')->with('success', 'Patient registered successfully!');
    }

    /**
     * Show form to edit patient information
     */
    public function editPatient($petId)
    {
        $pet = Pet::findOrFail($petId);
        $owners = User::where('role', 'Pet Owner')->orderBy('full_name')->get();
        $species = ['Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster', 'Guinea Pig', 'Fish', 'Other'];

        return view('staff.patients.edit', compact('pet', 'owners', 'species'));
    }

    /**
     * Update patient information
     */
    public function updatePatient(Request $request, $petId)
    {
        $pet = Pet::findOrFail($petId);

        $validated = $request->validate([
            'pet_name' => 'required|string|max:100',
            'species' => 'required|string|max:50',
            'breed' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before:today',
            'weight' => 'required|numeric|min:0.1|max:200',
            'sex' => 'required|in:Male,Female,Other',
        ]);

        $pet->update($validated);

        return redirect()->route('staff.patients.details', $petId)
                       ->with('success', 'Patient information updated successfully!');
    }

  
    public function prescriptions(Request $request)
    {
        $query = EPrescription::with([
            'medicalRecord.pet.owner',
            'medicalRecord.consultation.veterinarian',
            'adherenceLogs',
        ]);

        // Search by medication name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($prescriptionQuery) use ($search) {
                $prescriptionQuery->where('medication_name', 'like', "%{$search}%")
                    ->orWhereHas('medicalRecord.pet', function ($petQuery) use ($search) {
                        $petQuery->where('pet_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('issued_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('issued_at', '<=', $request->input('to_date') . ' 23:59:59');
        }

        $prescriptions = $query->orderBy('issued_at', 'desc')->paginate(20);

        return view('staff.prescriptions.index', compact('prescriptions'));
    }

    /**
     * View prescription details
     */
    public function prescriptionDetails($prescriptionId)
    {
        $prescription = EPrescription::with([
            'medicalRecord.pet.owner',
            'medicalRecord.consultation.veterinarian',
            'adherenceLogs' => fn ($query) => $query->orderByDesc('scheduled_datetime'),
        ])->findOrFail($prescriptionId);

        // Calculate adherence percentage
        $adherenceLogs = $prescription->adherenceLogs;
        $adherencePercentage = $adherenceLogs->count() > 0
            ? round(($adherenceLogs->where('intake_status', 'Taken')->count() / $adherenceLogs->count()) * 100, 2)
            : 0;

        return view('staff.prescriptions.details', compact('prescription', 'adherencePercentage'));
    }

    /**
     * View medical records
     */
    public function medicalRecords(Request $request)
    {
        $query = MedicalRecord::with([
            'pet.owner',
            'consultation.veterinarian',
            'prescriptions',
        ]);

        // Search by pet name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('pet', function ($q) use ($search) {
                $q->where('pet_name', 'like', "%{$search}%")
                  ->orWhere('species', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date') . ' 23:59:59');
        }

        $medicalRecords = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('medical-records.staff-index', compact('medicalRecords'));
    }

    /**
     * View medical record details
     */
    public function medicalRecordDetails($recordId)
    {
        $medicalRecord = MedicalRecord::with([
            'pet.owner',
            'consultation.veterinarian',
            'prescriptions' => fn ($query) => $query->orderByDesc('issued_at'),
            'prescriptions.adherenceLogs' => fn ($query) => $query->orderByDesc('scheduled_datetime'),
        ])->findOrFail($recordId);

        return view('medical-records.staff-details', compact('medicalRecord'));
    }

    /**
     * SECTION 2.5: REPORTING SYSTEM
     */

    /**
     * View reporting dashboard
     */
    public function reports()
    {
        // Get date range for reports (default: current month)
        $fromDate = request()->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = request()->input('to_date', now()->endOfMonth()->toDateString());

        // Appointment Statistics
        $totalAppointments = Appointment::whereBetween('appointment_date', [$fromDate, $toDate])->count();
        $approvedAppointments = Appointment::where('status', 'Approved')
            ->whereBetween('appointment_date', [$fromDate, $toDate])->count();
        $completedAppointments = Appointment::where('status', 'Completed')
            ->whereBetween('appointment_date', [$fromDate, $toDate])->count();
        $cancelledAppointments = Appointment::where('status', 'Cancelled')
            ->whereBetween('appointment_date', [$fromDate, $toDate])->count();
        $missedAppointments = Appointment::where('status', 'Missed')
            ->whereBetween('appointment_date', [$fromDate, $toDate])->count();

        // Appointment distribution by mode
        $appointmentsByMode = Appointment::selectRaw('consultation_mode, count(*) as count')
            ->whereBetween('appointment_date', [$fromDate, $toDate])
            ->groupBy('consultation_mode')
            ->get();

        // Consultation Statistics
        $totalConsultations = Consultation::whereDate('consultation_date', '>=', $fromDate)
            ->whereDate('consultation_date', '<=', $toDate)
            ->count();
        $completedConsultations = Consultation::where('status', 'Completed')
            ->whereDate('consultation_date', '>=', $fromDate)
            ->whereDate('consultation_date', '<=', $toDate)
            ->count();

        // Prescription Statistics
        $totalPrescriptions = EPrescription::whereDate('issued_at', '>=', $fromDate)
            ->whereDate('issued_at', '<=', $toDate)
            ->count();
        $prescriptionsByCompliance = AdherenceLog::selectRaw('intake_status, count(*) as count')
            ->whereDate('scheduled_datetime', '>=', $fromDate)
            ->whereDate('scheduled_datetime', '<=', $toDate)
            ->groupBy('intake_status')
            ->get();

        // Medical Records
        $totalMedicalRecords = MedicalRecord::whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->count();

        return view('staff.reports.dashboard', compact(
            'totalAppointments',
            'approvedAppointments',
            'completedAppointments',
            'cancelledAppointments',
            'missedAppointments',
            'appointmentsByMode',
            'totalConsultations',
            'completedConsultations',
            'totalPrescriptions',
            'prescriptionsByCompliance',
            'totalMedicalRecords',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Generate detailed appointment report
     */
    public function appointmentReport(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->endOfMonth()->toDateString());

        $appointments = Appointment::with('pet.owner', 'consultation.veterinarian')
            ->whereBetween('appointment_date', [$fromDate, $toDate]);

        // Filter by status
        if ($request->filled('status')) {
            $appointments->where('status', $request->input('status'));
        }

        $appointments = $appointments->orderBy('appointment_date', 'desc')->paginate(30);

        $stats = [
            'total' => $appointments->total(),
            'approved' => $this->countAppointmentsByStatus('Approved', $fromDate, $toDate),
            'completed' => $this->countAppointmentsByStatus('Completed', $fromDate, $toDate),
            'cancelled' => $this->countAppointmentsByStatus('Cancelled', $fromDate, $toDate),
            'missed' => $this->countAppointmentsByStatus('Missed', $fromDate, $toDate),
        ];

        return view('staff.reports.appointments', compact('appointments', 'stats', 'fromDate', 'toDate'));
    }

    /**
     * Generate consultation report
     */
    public function consultationReport(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->endOfMonth()->toDateString());

        $consultations = Consultation::with('appointment.pet.owner', 'veterinarian', 'messages')
            ->whereDate('consultation_date', '>=', $fromDate)
            ->whereDate('consultation_date', '<=', $toDate);

        // Filter by veterinarian
        if ($request->filled('veterinarian_id')) {
            $consultations->where('veterinarian_id', $request->input('veterinarian_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $consultations->where('status', $request->input('status'));
        }

        $consultations = $consultations->orderBy('consultation_date', 'desc')->paginate(30);

        $veterinarians = User::where('role', 'Veterinarian')->orderBy('full_name')->get();
        $statuses = ['Open', 'In Progress', 'Completed', 'Cancelled'];

        $stats = [
            'total' => $consultations->total(),
            'completed' => $this->countConsultationsByStatus('Completed', $fromDate, $toDate),
            'in_progress' => $this->countConsultationsByStatus('In Progress', $fromDate, $toDate),
        ];

        return view('staff.reports.consultations', compact('consultations', 'veterinarians', 'statuses', 'stats', 'fromDate', 'toDate'));
    }

    /**
     * Generate prescription adherence report
     */
    public function prescriptionReport(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->endOfMonth()->toDateString());

        $prescriptions = EPrescription::with([
            'medicalRecord.pet.owner',
            'medicalRecord.consultation.veterinarian',
            'adherenceLogs',
        ])->whereDate('issued_at', '>=', $fromDate)
            ->whereDate('issued_at', '<=', $toDate);

        // Filter by medication
        if ($request->filled('medication')) {
            $prescriptions->where('medication_name', 'like', '%' . $request->input('medication') . '%');
        }

        $prescriptions = $prescriptions->orderBy('issued_at', 'desc')->paginate(30);

        // Calculate compliance rates
        $prescriptions->each(function ($prescription) {
            $logs = $prescription->adherenceLogs;
            $prescription->compliance_rate = $logs->count() > 0
                ? round(($logs->where('intake_status', 'Taken')->count() / $logs->count()) * 100, 2)
                : 0;
        });

        $stats = [
            'total' => $prescriptions->total(),
            'high_compliance' => $this->countHighCompliancePrescriptions($fromDate, $toDate),
            'low_compliance' => $this->countLowCompliancePrescriptions($fromDate, $toDate),
        ];

        return view('staff.reports.prescriptions', compact('prescriptions', 'stats', 'fromDate', 'toDate'));
    }

    /**
     * Export appointment report to CSV
     */
    public function exportAppointmentReport(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->endOfMonth()->toDateString());

        $appointments = Appointment::with('pet.owner')
            ->whereBetween('appointment_date', [$fromDate, $toDate])
            ->orderBy('appointment_date', 'desc')
            ->get();

        $filename = 'appointment_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return response()->streamDownload(function () use ($appointments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Time', 'Pet Name', 'Owner', 'Species', 'Breed', 'Mode', 'Status', 'Reason']);

            foreach ($appointments as $appointment) {
                fputcsv($handle, [
                    $appointment->appointment_date,
                    $appointment->appointment_time,
                    $appointment->pet->pet_name,
                    $appointment->pet->owner->full_name,
                    $appointment->pet->species,
                    $appointment->pet->breed,
                    $appointment->consultation_mode,
                    $appointment->status,
                    $appointment->reason_for_visit,
                ]);
            }

            fclose($handle);
        }, $filename);
    }

    /**
     * Export consultation report to CSV
     */
    public function exportConsultationReport(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->endOfMonth()->toDateString());

        $consultations = Consultation::with('appointment.pet.owner', 'veterinarian')
            ->whereBetween('consultation_date', [$fromDate, $toDate])
            ->orderBy('consultation_date', 'desc')
            ->get();

        $filename = 'consultation_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return response()->streamDownload(function () use ($consultations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Pet', 'Owner', 'Veterinarian', 'Chief Complaint', 'Status', 'Message Count']);

            foreach ($consultations as $consultation) {
                fputcsv($handle, [
                    $consultation->consultation_date?->format('Y-m-d H:i'),
                    $consultation->appointment?->pet?->pet_name ?? 'N/A',
                    $consultation->appointment?->pet?->owner?->full_name ?? 'N/A',
                    $consultation->veterinarian?->full_name ?? 'N/A',
                    $consultation->chief_complaint,
                    $consultation->status,
                    $consultation->messages->count(),
                ]);
            }

            fclose($handle);
        }, $filename);
    }

    /**
     * Helper: Count appointments by status
     */
    private function countAppointmentsByStatus($status, $fromDate, $toDate)
    {
        return Appointment::where('status', $status)
            ->whereBetween('appointment_date', [$fromDate, $toDate])
            ->count();
    }

    /**
     * Helper: Count consultations by status
     */
    private function countConsultationsByStatus($status, $fromDate, $toDate)
    {
        return Consultation::where('status', $status)
            ->whereDate('consultation_date', '>=', $fromDate)
            ->whereDate('consultation_date', '<=', $toDate)
            ->count();
    }

    /**
     * Helper: Build the categorized pending appointment queue data.
     */
    private function pendingAppointmentQueueData(): array
    {
        $pendingAppointments = Appointment::where('status', 'Pending')
            ->with(['pet.owner'])
            ->orderBy('appointment_date')
            ->get();

        $emergencyQueue = collect();

        $highPriorityQueue = $pendingAppointments;

        $routineQueue = $pendingAppointments->filter(function ($appointment) use ($emergencyQueue, $highPriorityQueue) {
            return ! $emergencyQueue->contains('id', $appointment->id)
                && ! $highPriorityQueue->contains('id', $appointment->id);
        });

        return compact('pendingAppointments', 'emergencyQueue', 'highPriorityQueue', 'routineQueue');
    }

    /**
     * Build the live staff appointment notification feed.
     */
    private function staffNotificationData(): array
    {
        $manilaNow = now('Asia/Manila');
        $lastSyncedAt = $manilaNow->copy();
        $todayStartUtc = $manilaNow->copy()->startOfDay()->utc();
        $todayEndUtc = $manilaNow->copy()->endOfDay()->utc();
        $recentWindowStartUtc = $manilaNow->copy()->subHours(24)->utc();
        $dismissedKeys = StaffNotificationDismissal::where('user_id', auth()->id())
            ->pluck('notification_key')
            ->all();

        $newAppointmentEntries = Appointment::with('pet.owner')
            ->where('created_at', '>=', $recentWindowStartUtc)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->reject(fn (Appointment $appointment) => in_array(
                $this->staffNotificationKey('new-appointment', $appointment->appointment_id),
                $dismissedKeys,
                true
            ))
            ->values();

        $cancelledTodayAppointments = Appointment::with('pet.owner')
            ->where('status', 'Cancelled')
            ->whereBetween('updated_at', [$todayStartUtc, $todayEndUtc])
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get()
            ->reject(fn (Appointment $appointment) => in_array(
                $this->staffNotificationKey('cancelled-appointment', $appointment->appointment_id),
                $dismissedKeys,
                true
            ))
            ->values();

        $recentDidNotArriveAppointments = Appointment::with('pet.owner')
            ->where('status', 'Missed')
            ->where('updated_at', '>=', $recentWindowStartUtc)
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get()
            ->reject(fn (Appointment $appointment) => in_array(
                $this->staffNotificationKey('did-not-arrive', $appointment->appointment_id),
                $dismissedKeys,
                true
            ))
            ->values();

        $adherenceNotifications = AdherenceNotification::with([
            'user',
            'adherenceLog.prescription.medicalRecord.pet.owner',
        ])
            ->where('status', 'Confirmed')
            ->whereNotNull('confirmed_at')
            ->where(function ($query) use ($recentWindowStartUtc) {
                $query->where('confirmed_at', '>=', $recentWindowStartUtc)
                    ->orWhere('scheduled_at', '>=', $recentWindowStartUtc);
            })
            ->orderByDesc('confirmed_at')
            ->limit(12)
            ->get()
            ->reject(fn (AdherenceNotification $notification) => in_array(
                $this->staffNotificationKey('adherence', $notification->notification_id),
                $dismissedKeys,
                true
            ))
            ->values();

        $totalLiveAlerts = $newAppointmentEntries->count()
            + $cancelledTodayAppointments->count()
            + $recentDidNotArriveAppointments->count()
            + $adherenceNotifications->count();

        $staffNotifications = $this->buildStaffNotificationRows(
            $newAppointmentEntries,
            $cancelledTodayAppointments,
            $recentDidNotArriveAppointments,
            $adherenceNotifications
        );

        return compact(
            'manilaNow',
            'lastSyncedAt',
            'newAppointmentEntries',
            'cancelledTodayAppointments',
            'recentDidNotArriveAppointments',
            'adherenceNotifications',
            'staffNotifications',
            'totalLiveAlerts'
        );
    }

    private function buildStaffNotificationRows(
        Collection $newAppointmentEntries,
        Collection $cancelledTodayAppointments,
        Collection $recentDidNotArriveAppointments,
        Collection $adherenceNotifications
    ): array {
        $rows = [];

        foreach ($newAppointmentEntries as $appointment) {
            $rows[] = $this->appointmentNotificationRow(
                'New Appointment',
                'new-appointment',
                'type-new',
                $appointment,
                $appointment->created_at?->copy()->timezone('Asia/Manila'),
                strtolower($appointment->consultation_mode ?? 'clinic') . ' appointment',
                route('staff.appointments.confirm', $appointment),
                true
            );
        }

        foreach ($cancelledTodayAppointments as $appointment) {
            $rows[] = $this->appointmentNotificationRow(
                'Appointment Cancelled',
                'cancelled-appointment',
                'type-cancelled',
                $appointment,
                $appointment->updated_at?->copy()->timezone('Asia/Manila'),
                'cancelled appointment',
                null,
                false
            );
        }

        foreach ($recentDidNotArriveAppointments as $appointment) {
            $rows[] = $this->appointmentNotificationRow(
                'Did Not Arrive',
                'did-not-arrive',
                'type-missed',
                $appointment,
                $appointment->updated_at?->copy()->timezone('Asia/Manila'),
                'missed appointment',
                null,
                false
            );
        }

        foreach ($adherenceNotifications as $notification) {
            $prescription = $notification->adherenceLog?->prescription;
            $pet = $prescription?->medicalRecord?->pet;
            $owner = $pet?->owner ?? $notification->user;

            $rows[] = [
                'type' => 'Adherence',
                'route_type' => 'adherence',
                'id' => $notification->notification_id,
                'pet' => $pet?->name ?? 'Unknown Pet',
                'owner' => $owner?->name ?? $owner?->full_name ?? 'Unknown Owner',
                'message' => "{$notification->medication_name} {$notification->dosage} taken as scheduled",
                'time' => $notification->confirmed_at?->copy()->timezone('Asia/Manila')
                    ?? $notification->scheduled_at?->copy()->timezone('Asia/Manila'),
                'route' => $prescription ? route('staff.prescriptions.details', $prescription) : null,
                'can_view' => $prescription !== null,
                'class' => 'type-adherence',
            ];
        }

        usort($rows, fn ($a, $b) => $b['time']?->getTimestamp() <=> $a['time']?->getTimestamp());

        return $rows;
    }

    private function appointmentNotificationRow(
        string $type,
        string $routeType,
        string $class,
        Appointment $appointment,
        ?Carbon $time,
        string $message,
        ?string $route,
        bool $canView
    ): array {
        return [
            'type' => $type,
            'route_type' => $routeType,
            'pet' => $appointment->pet?->name ?? 'Unknown Pet',
            'owner' => $appointment->pet?->owner?->name ?? $appointment->pet?->owner?->full_name ?? 'Unknown Owner',
            'message' => $message,
            'time' => $time,
            'id' => $appointment->appointment_id,
            'route' => $route,
            'can_view' => $canView,
            'class' => $class,
        ];
    }

    private function staffNotificationKey(string $type, int $id): string
    {
        return "{$type}:{$id}";
    }

    /**
     * Helper: Count high compliance prescriptions
     */
    private function countHighCompliancePrescriptions($fromDate, $toDate)
    {
        return EPrescription::whereBetween('issued_at', [$fromDate, $toDate])
            ->with('adherenceLogs')
            ->get()
            ->filter(function ($prescription) {
                $logs = $prescription->adherenceLogs;
                if ($logs->count() === 0) return false;
                $compliance = ($logs->where('intake_status', 'Taken')->count() / $logs->count()) * 100;
                return $compliance >= 80;
            })
            ->count();
    }

    /**
     * Helper: Count low compliance prescriptions
     */
    private function countLowCompliancePrescriptions($fromDate, $toDate)
    {
        return EPrescription::whereBetween('issued_at', [$fromDate, $toDate])
            ->with('adherenceLogs')
            ->get()
            ->filter(function ($prescription) {
                $logs = $prescription->adherenceLogs;
                if ($logs->count() === 0) return false;
                $compliance = ($logs->where('intake_status', 'Taken')->count() / $logs->count()) * 100;
                return $compliance < 80;
            })
            ->count();
    }

    /**
     * Display all clients (pet owners)
     */
    public function clients()
    {
        $clients = User::where('role', 'Pet Owner')->paginate(20);
        return view('staff.clients', compact('clients'));
    }

    /**
     * Show client registration form
     */
    public function registerClient()
    {
        return view('staff.register-client');
    }

    /**
     * Store new client
     */
    public function storeClient(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'contact_no' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['role'] = 'Pet Owner';
        $validated['status'] = 'Active';

        User::create($validated);

        return redirect()->route('staff.clients')->with('success', 'Client registered successfully');
    }

    /**
     * Display all pets
     */
    public function pets()
    {
        $query = Pet::with('owner');
        
        // Filter by owner if provided
        if (request('owner_id')) {
            $query->where('user_id', request('owner_id'));
        }
        
        $pets = $query->paginate(20);
        $owners = User::where('role', 'Pet Owner')->get();
        return view('staff.pets', compact('pets', 'owners'));
    }

    /**
     * Show pet creation form
     */
    public function createPet()
    {
        $owners = User::where('role', 'Pet Owner')->get();
        return view('staff.add-pet', compact('owners'));
    }

    /**
     * Store new pet
     */
    public function storePet(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'pet_name' => 'required|string|max:255',
            'species' => 'required|string|max:100',
            'breed' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'weight' => 'nullable|numeric',
            'sex' => 'nullable|in:Male,Female',
        ]);

        Pet::create($validated);

        return redirect()->route('staff.pets')->with('success', 'Pet added successfully');
    }

    /**
     * Show pet edit form
     */
    public function editPet($id)
    {
        $pet = Pet::findOrFail($id);
        $owners = User::where('role', 'Pet Owner')->get();
        return view('staff.edit-pet', compact('pet', 'owners'));
    }

    /**
     * Update pet in database
     */
    public function updatePet(Request $request, $id)
    {
        $pet = Pet::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'pet_name' => 'required|string|max:255',
            'species' => 'required|string|max:100',
            'breed' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'weight' => 'nullable|numeric',
            'sex' => 'nullable|in:Male,Female',
        ]);

        $pet->update($validated);

        return redirect()->route('staff.pets')->with('success', 'Pet updated successfully');
    }

    /**
     * Show delete confirmation page
     */
    public function confirmDeletePet($id)
    {
        $pet = Pet::findOrFail($id);
        return view('staff.confirm-delete-pet', compact('pet'));
    }

    /**
     * Delete pet from database
     */
    public function deletePet($id)
    {
        $pet = Pet::findOrFail($id);
        $petName = $pet->pet_name;
        $pet->delete();

        return redirect()->route('staff.pets')->with('success', "Pet '{$petName}' has been deleted.");
    }
}
