<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Pet;
use App\Models\SymptomLog;
use App\Models\User;
use App\Models\MedicalRecord;
use App\Models\EPrescription;
use App\Models\AdherenceLog;
use App\Services\AppointmentScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    /**
     * Display the staff dashboard
     */
    public function dashboard()
    {
        $allAppointments = Appointment::count();
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $pending = Appointment::where('status', 'Pending')->count();
        $emergencies = SymptomLog::where('concern_level', 'emergency')->count();
        
        // Get appointment requests awaiting confirmation
        $pendingRequests = Appointment::where('status', 'Pending')
            ->where('appointment_date', '>=', now()->toDateString())
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get upcoming approved appointments
        $upcomingApprovedAppointments = Appointment::where('status', 'Approved')
            ->where('appointment_date', '>=', now()->toDateString())
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get today's appointments
        $todayListAppointments = Appointment::whereDate('appointment_date', today())
            ->with('pet.owner')
            ->orderBy('appointment_time', 'asc')
            ->get();
        
        // Get emergency appointments (where pet has emergency symptom log)
        $emergencyAppointments = Appointment::whereIn('pet_id', 
            SymptomLog::where('concern_level', 'emergency')
                ->distinct()
                ->pluck('pet_id')
        )
            ->where('appointment_date', '>=', now()->toDateString())
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get regular check-ups (appointments not related to emergencies)
        $regularAppointments = Appointment::where('status', 'Approved')
            ->where('appointment_date', '>=', now()->toDateString())
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get();
        
        // Get missed approved appointments (past appointments that are still approved)
        $missedApprovedAppointments = Appointment::where(function ($query) {
            $query->where('status', 'Missed')
                ->orWhere(function ($approvedQuery) {
                    $approvedQuery->where('status', 'Approved')
                        ->where('appointment_date', '<', now()->toDateString());
                });
        })
            ->with('pet.owner')
            ->orderBy('appointment_date', 'desc')
            ->get();

        // Get recent pending appointments for activity summary
        $recentPending = Appointment::where('status', 'Pending')
            ->where('appointment_date', '>=', now()->toDateString())
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->limit(5)
            ->get();

        return view('staff.dashboard', compact('allAppointments', 'todayAppointments', 'pending', 'emergencies', 'pendingRequests', 'upcomingApprovedAppointments', 'todayListAppointments', 'emergencyAppointments', 'regularAppointments', 'missedApprovedAppointments', 'recentPending'));
    }

    /**
     * Return upcoming approved appointments as JSON for the calendar
     */
    public function upcomingAppointmentsJson()
    {
        $appointments = Appointment::where('status', 'Approved')
            ->where('appointment_date', '>=', now()->toDateString())
            ->with('pet.owner')
            ->orderBy('appointment_date', 'asc')
            ->get()
            ->map(function ($apt) {
                return [
                    'appointment_id' => $apt->appointment_id,
                    'appointment_date' => $apt->appointment_date?->toDateTimeString(),
                    'reason' => $apt->reason,
                    'status' => $apt->status,
                    'pet' => [
                        'name' => $apt->pet?->name,
                        'species' => $apt->pet?->species,
                    ],
                    'owner' => [
                        'name' => $apt->pet?->owner?->name,
                        'phone' => $apt->pet?->owner?->phone,
                    ],
                ];
            });

        return response()->json(['appointments' => $appointments]);
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
        $bookedAppointments = AppointmentScheduleService::bookedAppointments($appointment->id);

        return view('staff.confirm-appointment', compact('appointment', 'vets', 'bookedAppointments'));
    }

    /**
     * Show create appointment form
     */
    public function createAppointment()
    {
        $pets = Pet::with('owner')->get();
        $vets = User::where('role', 'Veterinarian')->get();
        $unavailablePetIds = Appointment::query()->activeEntries()->pluck('pet_id')->all();
        $bookedAppointments = AppointmentScheduleService::bookedAppointments();

        return view('staff.create-appointment', compact('pets', 'vets', 'unavailablePetIds', 'bookedAppointments'));
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
        $validator->after(function ($validator) use ($request) {
            if ($request->filled('pet_id') && Appointment::petHasActiveEntry((int) $request->input('pet_id'))) {
                return;
            }

            AppointmentScheduleService::validateSlot($validator, $request);
        });
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
        $appointment = Appointment::findOrFail($id);

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
        $validator->after(fn ($validator) => AppointmentScheduleService::validateSlot($validator, $request, $appointment->id));
        $validated = $validator->validate();

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'consultation_mode' => $validated['consultation_mode'],
            'status' => $validated['status'],
        ]);

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

    /**
     * SECTION 2.3: TELECONSULTATION SCHEDULE MANAGEMENT
     */

    /**
     * SECTION 2.4: E-PRESCRIPTIONS AND MEDICAL RECORDS ACCESS
     */

    /**
     * View all prescriptions
     */
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

        return view('staff.medical-records.index', compact('medicalRecords'));
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

        return view('staff.medical-records.details', compact('medicalRecord'));
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

        // Appointment distribution by mode
        $appointmentsByMode = Appointment::selectRaw('consultation_mode, count(*) as count')
            ->whereBetween('appointment_date', [$fromDate, $toDate])
            ->groupBy('consultation_mode')
            ->get();

        // Consultation Statistics
        $totalConsultations = Consultation::whereBetween('consultation_date', [$fromDate, $toDate])->count();
        $completedConsultations = Consultation::where('status', 'Completed')
            ->whereBetween('consultation_date', [$fromDate, $toDate])->count();

        // Prescription Statistics
        $totalPrescriptions = EPrescription::whereBetween('issued_at', [$fromDate, $toDate])->count();
        $prescriptionsByCompliance = AdherenceLog::selectRaw('intake_status, count(*) as count')
            ->whereBetween('scheduled_datetime', [$fromDate, $toDate])
            ->groupBy('intake_status')
            ->get();

        // Medical Records
        $totalMedicalRecords = MedicalRecord::whereBetween('created_at', [$fromDate, $toDate])->count();

        return view('staff.reports.dashboard', compact(
            'totalAppointments',
            'approvedAppointments',
            'completedAppointments',
            'cancelledAppointments',
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
            ->whereBetween('consultation_date', [$fromDate, $toDate]);

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
        ])->whereBetween('issued_at', [$fromDate, $toDate]);

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
            ->whereBetween('consultation_date', [$fromDate, $toDate])
            ->count();
    }

    /**
     * Helper: Build the categorized pending appointment queue data.
     */
    private function pendingAppointmentQueueData(): array
    {
        $totalPendingAppointments = Appointment::where('status', 'Pending')->count();
        $pendingAppointments = Appointment::where('status', 'Pending')
            ->with(['pet.owner', 'pet.symptomLogs'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $emergencyQueue = $pendingAppointments->filter(function ($appointment) {
            $latestSymptom = $appointment->pet->symptomLogs->last();

            return $latestSymptom && in_array($latestSymptom->concern_level, ['emergency', 'urgent', 'critical'], true);
        });

        $highPriorityQueue = $pendingAppointments->filter(function ($appointment) {
            $latestSymptom = $appointment->pet->symptomLogs->last();

            return $latestSymptom && in_array($latestSymptom->concern_level, ['high priority', 'vet_visit', 'priority'], true);
        });

        $routineQueue = $pendingAppointments->filter(function ($appointment) use ($emergencyQueue, $highPriorityQueue) {
            return ! $emergencyQueue->contains('id', $appointment->id)
                && ! $highPriorityQueue->contains('id', $appointment->id);
        });

        return compact('pendingAppointments', 'totalPendingAppointments', 'emergencyQueue', 'highPriorityQueue', 'routineQueue');
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

        $newAppointmentEntries = Appointment::with('pet.owner')
            ->where('created_at', '>=', $recentWindowStartUtc)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $cancelledTodayAppointments = Appointment::with('pet.owner')
            ->where('status', 'Cancelled')
            ->whereBetween('updated_at', [$todayStartUtc, $todayEndUtc])
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $recentDidNotArriveAppointments = Appointment::with('pet.owner')
            ->where('status', 'Missed')
            ->where('updated_at', '>=', $recentWindowStartUtc)
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $totalLiveAlerts = $newAppointmentEntries->count()
            + $cancelledTodayAppointments->count()
            + $recentDidNotArriveAppointments->count();

        return compact(
            'manilaNow',
            'lastSyncedAt',
            'newAppointmentEntries',
            'cancelledTodayAppointments',
            'recentDidNotArriveAppointments',
            'totalLiveAlerts'
        );
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
}
