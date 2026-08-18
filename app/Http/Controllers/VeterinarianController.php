<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\AdherenceLog;
use App\Services\AdherenceService;
use App\Services\AppointmentScheduleService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VeterinarianController extends Controller
{
    private function currentVetId(): int
    {
        return (int) Auth::id();
    }

    private function vetAppointmentsQuery()
    {
        return Appointment::with(['pet.owner', 'consultation.veterinarian'])
            ->where(function ($query) {
                $query->whereHas('consultation', function ($consultationQuery) {
                    $consultationQuery->where('veterinarian_id', $this->currentVetId());
                })->orWhereDoesntHave('consultation');
            });
    }

    /**
     * Display the veterinarian dashboard
     */
    public function dashboard()
    {
        $vet = Auth::user();
        $vetId = $this->currentVetId();

        $prescriptions = EPrescription::with([
            'adherenceLogs' => fn ($q) => $q->orderByDesc('scheduled_datetime'),
        ])->get();

        $totalPrescriptions = $prescriptions->count();
        $prescriptionsWithLogs = $prescriptions->filter(fn ($p) => $p->adherenceLogs->isNotEmpty())->count();
        $adherenceRate = $totalPrescriptions > 0 
            ? round(($prescriptionsWithLogs / $totalPrescriptions) * 100, 2)
            : 0;

        $todayAppointments = $this->vetAppointmentsQuery()
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['Pending', 'Approved', 'Rescheduled'])
            ->count();

        $upcomingAppointments = $this->vetAppointmentsQuery()
            ->whereDate('appointment_date', '>=', today())
            ->whereIn('status', ['Pending', 'Approved', 'Rescheduled'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(5)
            ->get();

        $recentConsultations = Consultation::with(['appointment.pet.owner', 'veterinarian'])
            ->where('veterinarian_id', $vetId)
            ->latest('consultation_date')
            ->limit(5)
            ->get();

        $recentPrescriptions = EPrescription::with('medicalRecord.pet.owner')
            ->whereHas('medicalRecord.consultation', function ($query) use ($vetId) {
                $query->where('veterinarian_id', $vetId);
            })
            ->latest('issued_at')
            ->limit(5)
            ->get();

        $lowAdherenceCases = EPrescription::with(['medicalRecord.pet.owner', 'adherenceLogs'])
            ->whereHas('medicalRecord.consultation', function ($query) use ($vetId) {
                $query->where('veterinarian_id', $vetId);
            })
            ->get()
            ->filter(function (EPrescription $prescription) {
                if ($prescription->adherenceLogs->isEmpty()) {
                    return true;
                }

                $taken = $prescription->adherenceLogs->where('intake_status', 'Taken')->count();
                $total = $prescription->adherenceLogs->count();

                return $total > 0 && ($taken / $total) < 0.7;
            })
            ->take(5)
            ->values();

        return view('veterinarian.dashboard', compact(
            'adherenceRate',
            'totalPrescriptions',
            'todayAppointments',
            'upcomingAppointments',
            'recentConsultations',
            'recentPrescriptions',
            'lowAdherenceCases'
        ));
    }

   
    
    public function appointments(Request $request)
    {
        $filter = $request->query('filter', 'all'); // 'today' or 'all'

        $query = $this->vetAppointmentsQuery();

        if ($filter === 'today') {
            $today = now()->toDateString();
            $query->whereDate('appointment_date', $today);
        }

        $pendingAppointments = (clone $query)
            ->where('status', 'Pending')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $approvedAppointments = (clone $query)
            ->where('status', 'Approved')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $todayAppointmentsCount = $this->vetAppointmentsQuery()
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['Pending', 'Approved', 'Rescheduled'])
            ->count();

        $upcomingAppointmentsCount = $this->vetAppointmentsQuery()
            ->whereDate('appointment_date', '>=', today())
            ->whereIn('status', ['Pending', 'Approved', 'Rescheduled'])
            ->count();

        return view('veterinarian.appointments', compact('pendingAppointments', 'approvedAppointments', 'filter', 'todayAppointmentsCount', 'upcomingAppointmentsCount'));
    }

    /**
     * Display medical records
     */
    public function medicalRecords(Request $request)
    {
        $medicalRecords = MedicalRecord::with([
            'pet.owner',
            'consultation.veterinarian',
            'prescriptions' => fn ($query) => $query->with([
                'adherenceLogs' => fn ($adherenceQuery) => $adherenceQuery->orderByDesc('scheduled_datetime'),
            ])->orderByDesc('issued_at'),
        ])->orderByDesc('updated_at')->orderByDesc('created_at')->get();

        $patients = $medicalRecords->pluck('pet')
            ->filter()
            ->unique('pet_id')
            ->sortByDesc(function (Pet $pet) use ($medicalRecords) {
                return optional($medicalRecords->firstWhere('pet_id', $pet->pet_id))->updated_at?->timestamp ?? 0;
            })
            ->values();

        return view('medical-records.veterinarian-index', compact(
            'medicalRecords',
            'patients'
        ));
    }

    public function medicalRecordDetails(MedicalRecord $record)
    {
        $record->load([
            'pet.owner',
            'consultation.veterinarian',
            'prescriptions' => fn ($query) => $query->with([
                'adherenceLogs' => fn ($adherenceQuery) => $adherenceQuery->orderByDesc('scheduled_datetime'),
            ])->orderByDesc('issued_at'),
        ]);

        return view('medical-records.veterinarian-details', [
            'record' => $record,
        ]);
    }

    /**
     * Display e-prescription module
     */
    public function prescriptions(Request $request)
    {
        $medicalRecords = MedicalRecord::with([
            'pet.owner',
            'prescriptions' => fn ($query) => $query->orderByDesc('issued_at'),
        ])->orderByDesc('created_at')->get();

        $selectedRecord = $medicalRecords->firstWhere('record_id', (int) $request->query('record'));

        if (!$selectedRecord) {
            $selectedRecord = $medicalRecords->first();
        }

        $prescriptions = EPrescription::with('medicalRecord.pet.owner')
            ->orderByDesc('issued_at')
            ->get();

        $patientCount = $medicalRecords->pluck('pet_id')->unique()->count();
        $issuedTodayCount = $prescriptions->filter(fn (EPrescription $prescription) => $prescription->issued_at?->isToday())->count();

        return view('veterinarian.prescriptions', compact('medicalRecords', 'selectedRecord', 'prescriptions', 'patientCount', 'issuedTodayCount'));
    }

    /**
     * Store a new e-prescription for one of the veterinarian's medical records
     */
    public function storePrescription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'record_id' => 'required|exists:medical_records,record_id',
            'medication_name' => 'required|string|max:100',
            'dosage' => 'required|string|max:50',
            'frequency' => 'required|string|max:50',
            'duration' => 'required|string|max:50',
        ]);

        $validator->after(function ($validator) use ($request) {
            $this->validatePrescriptionTimingInputs($validator, [
                'frequency' => $request->input('frequency'),
                'duration' => $request->input('duration'),
            ]);
        });

        $validated = $validator->validate();

        $medicalRecord = MedicalRecord::findOrFail($validated['record_id']);

        $this->issuePrescription($medicalRecord, $validated);

        return redirect()->route('vet.prescriptions', ['record' => $medicalRecord->record_id])
            ->with('success', 'E-prescription created successfully.');
    }

    /**
     * Display medication adherence monitoring dashboard
     */
    public function adherenceMonitoring(Request $request)
    {
        $vet = Auth::user();
        
        // Get all e-prescriptions
        $prescriptions = EPrescription::with([
            'medicalRecord.pet.owner',
            'adherenceLogs' => fn ($q) => $q->orderByDesc('scheduled_datetime'),
        ])->orderByDesc('issued_at')
            ->get();

        // Calculate adherence metrics
        $totalPrescriptions = $prescriptions->count();
        $prescriptionsWithLogs = $prescriptions->filter(fn ($p) => $p->adherenceLogs->isNotEmpty())->count();
        $adherenceRate = $totalPrescriptions > 0 
            ? round(($prescriptionsWithLogs / $totalPrescriptions) * 100, 2)
            : 0;

        // Get high-risk patients (low adherence)
        $lowAdherencePrescriptions = $prescriptions->filter(function ($p) {
            if ($p->adherenceLogs->isEmpty()) return true;
            $confirmed = $p->adherenceLogs->where('intake_status', 'Taken')->count();
            $total = $p->adherenceLogs->count();
            return ($confirmed / $total) < 0.7; // Less than 70% adherence
        });

        // Get selected prescription for detailed view
        $selectedPrescription = null;
        if ($request->has('prescription') && $request->query('prescription') !== '') {
            $selectedPrescription = $prescriptions->firstWhere('prescription_id', (int) $request->query('prescription'));
        }
        if (!$selectedPrescription) {
            $selectedPrescription = $prescriptions->first();
        }

        return view('veterinarian.adherence-monitoring', compact(
            'prescriptions',
            'selectedPrescription',
            'totalPrescriptions',
            'prescriptionsWithLogs',
            'adherenceRate',
            'lowAdherencePrescriptions'
        ));
    }

    /**
     * Update medication adherence status
     */
    public function updateAdherence(Request $request, EPrescription $prescription)
    {
        $validated = $request->validate([
            'scheduled_datetime' => 'required|date',
            'intake_status' => 'required|in:Taken,Missed,Delayed',
            'confirmation_time' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Use updateOrCreate to prevent duplicate logs for the same scheduled time
        AdherenceLog::updateOrCreate(
            [
                'prescription_id' => $prescription->prescription_id,
                'scheduled_datetime' => $validated['scheduled_datetime'],
            ],
            [
                'intake_status' => $validated['intake_status'],
                'confirmation_time' => $validated['confirmation_time'],
                'remarks' => $validated['remarks'],
            ]
        );

        return back()->with('success', 'Medication adherence logged successfully.');
    }

    /**
     * Show create appointment form
     */
    public function createAppointment()
    {
        $pets = Pet::with('owner')->get();
        $unavailablePetIds = Appointment::query()->activeEntries()->pluck('pet_id')->all();
        $calendarBookings = AppointmentScheduleService::calendarBookings();
        $calendarSlots = AppointmentScheduleService::WORKING_HOURS;

        return view('veterinarian.create-appointment', compact('pets', 'unavailablePetIds', 'calendarBookings', 'calendarSlots'));
    }

    /**
     * Store new appointment
     */
    public function storeAppointment(\Illuminate\Http\Request $request)
    {
        AppointmentScheduleService::normalizeDateTimeInput($request);

        $reason = $request->input('reason_for_visit') ?? $request->input('reason');
        $request->merge(['reason' => $reason]);

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
            'pet_id' => 'required|exists:pets,pet_id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'consultation_mode' => 'required|in:In-clinic,Teleconsultation',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:Pending,Approved,Rescheduled,Completed,Cancelled',
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
        // Create initial notification for Pending appointment
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, 'Pending');
        } catch (\Exception $e) {
            \Log::error('Error creating pending appointment notification', ['error' => $e->getMessage()]);
        }
        return redirect()->route('vet.appointments')->with('success', 'Appointment created successfully!');
    }

    /**
     * Display the live appointment session workspace
     */
    public function showAppointmentSession(Appointment $appointment)
    {
        $appointment->loadMissing('pet.owner');

        $consultation = $this->resolveAppointmentConsultation($appointment);

        if ($consultation->status === 'Open') {
            $consultation->update(['status' => 'In Progress']);
            $consultation->refresh();
        }

        $medicalRecord = MedicalRecord::with('prescriptions')
            ->where('consultation_id', $consultation->consultation_id)
            ->first();

        return view('veterinarian.appointment-session', compact('appointment', 'consultation', 'medicalRecord'));
    }

    /**
     * Save the appointment session, medical record, and optional prescription
     */
    public function storeAppointmentSession(Request $request, Appointment $appointment)
    {
        if (
            !$request->has('prescriptions')
            && $request->hasAny(['medication_name', 'dosage', 'frequency', 'duration'])
        ) {
            $request->merge([
                'prescriptions' => [[
                    'medication_name' => $request->input('medication_name'),
                    'dosage' => $request->input('dosage'),
                    'frequency' => $request->input('frequency'),
                    'duration' => $request->input('duration'),
                ]],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'chief_complaint' => 'required|string|max:1000',
            'consultation_notes' => 'nullable|string|max:5000',
            'diagnosis' => 'required|string|max:2000',
            'treatment_plan' => 'required|string|max:5000',
            'vaccination_notes' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
            'prescriptions' => 'nullable|array',
            'prescriptions.*.medication_name' => 'nullable|string|max:100',
            'prescriptions.*.dosage' => 'nullable|string|max:50',
            'prescriptions.*.frequency' => 'nullable|string|max:100',
            'prescriptions.*.duration' => 'nullable|string|max:50',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach (($request->input('prescriptions') ?? []) as $index => $prescription) {
                $normalized = collect([
                    'medication_name' => trim((string) ($prescription['medication_name'] ?? '')),
                    'dosage' => trim((string) ($prescription['dosage'] ?? '')),
                    'frequency' => trim((string) ($prescription['frequency'] ?? '')),
                    'duration' => trim((string) ($prescription['duration'] ?? '')),
                ]);

                $filledFields = $normalized->filter(fn ($value) => $value !== '');

                if ($filledFields->isEmpty()) {
                    continue;
                }

                foreach ($normalized as $field => $value) {
                    if ($value === '') {
                        $validator->errors()->add(
                            "prescriptions.$index.$field",
                            'Complete all prescription fields or leave the entire row blank.'
                        );
                    }
                }

                if ($filledFields->isNotEmpty()) {
                    $this->validatePrescriptionTimingInputs($validator, $normalized->all(), "prescriptions.$index");
                }
            }
        });

        $validated = $validator->validate();

        // Wrap entire operation in transaction to prevent partial saves
        return DB::transaction(function () use ($validated, $appointment) {
            $consultation = $this->resolveAppointmentConsultation($appointment);

            $consultation->update([
                'chief_complaint' => $validated['chief_complaint'],
                'consultation_notes' => $validated['consultation_notes'] ?? null,
                'consultation_date' => $consultation->consultation_date ?? $appointment->appointment_date ?? now(),
                'status' => 'Completed',
            ]);

            $medicalRecord = MedicalRecord::updateOrCreate(
                ['consultation_id' => $consultation->consultation_id],
                [
                    'pet_id' => $appointment->pet_id,
                    'diagnosis' => $validated['diagnosis'],
                    'treatment_plan' => $validated['treatment_plan'],
                    'vaccination_notes' => $validated['vaccination_notes'] ?? null,
                    'follow_up_date' => $validated['follow_up_date'] ?? null,
                ]
            );

            $prescriptions = collect($validated['prescriptions'] ?? [])
                ->map(function (array $prescription) {
                    return [
                        'medication_name' => trim((string) ($prescription['medication_name'] ?? '')),
                        'dosage' => trim((string) ($prescription['dosage'] ?? '')),
                        'frequency' => trim((string) ($prescription['frequency'] ?? '')),
                        'duration' => trim((string) ($prescription['duration'] ?? '')),
                    ];
                })
                ->filter(fn (array $prescription) => collect($prescription)->filter()->isNotEmpty())
                ->values();

            foreach ($prescriptions as $prescription) {
                $this->issuePrescription($medicalRecord, $prescription);
            }

            $appointment->update(['status' => 'Completed']);

            // Create new notification with 'Completed' status
            try {
                AppointmentNotification::recordStatus($appointment->appointment_id, 'Completed');
                \Log::info('Appointment completion notification created', [
                    'appointment_id' => $appointment->appointment_id,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error creating completion notification', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $appointment->appointment_id,
                ]);
            }

            return redirect()
                ->route('vet.appointments.session', $appointment)
                ->with('success', 'Appointment session saved successfully.');
        });
    }

    /**
     * Mark an appointment as missed when the pet owner does not arrive
     */
    public function markAppointmentDidNotArrive(Appointment $appointment)
    {
        $consultation = Consultation::where('appointment_id', $appointment->appointment_id)
            ->latest('consultation_date')
            ->first();

        if ($consultation && (int) $consultation->veterinarian_id !== (int) Auth::id()) {
            abort(403, 'This appointment is assigned to another veterinarian.');
        }

        $consultation ??= $this->resolveAppointmentConsultation($appointment);
        $consultation->update(['status' => 'Missed']);

        $appointment->update(['status' => 'Missed']);

        // Record the missed status for this appointment.
        AppointmentNotification::recordStatus($appointment->appointment_id, 'Missed');

        return redirect()
            ->route('vet.appointments')
            ->with('success', 'Appointment marked as did not arrive.');
    }

    private function resolveAppointmentConsultation(Appointment $appointment): Consultation
    {
        $consultation = Consultation::where('appointment_id', $appointment->appointment_id)
            ->latest('consultation_date')
            ->first();

        if ($consultation) {
            abort_if(
                (int) $consultation->veterinarian_id !== (int) Auth::id(),
                403,
                'This appointment session is assigned to another veterinarian.'
            );

            return $consultation;
        }

        return Consultation::create([
            'appointment_id' => $appointment->appointment_id,
            'veterinarian_id' => Auth::id(),
            'chief_complaint' => $appointment->reason ?? 'Appointment session opened',
            'ai_guidance_summary' => null,
            'consultation_notes' => null,
            'consultation_date' => $appointment->appointment_date ?? now(),
            'status' => 'Open',
        ]);
    }

    private function validatePrescriptionTimingInputs($validator, array $prescription, string $prefix = ''): void
    {
        $frequencyKey = $prefix !== '' ? "{$prefix}.frequency" : 'frequency';
        $durationKey = $prefix !== '' ? "{$prefix}.duration" : 'duration';

        if (AdherenceService::frequencyPerDayFromValue($prescription['frequency'] ?? null) === null) {
            $validator->errors()->add($frequencyKey, 'Frequency must be a number from 1 to 5 doses per day.');
        }

        if (AdherenceService::durationDaysFromValue($prescription['duration'] ?? null) === null) {
            $validator->errors()->add($durationKey, 'Duration must include a valid number of days.');
        }
    }

    private function issuePrescription(MedicalRecord $medicalRecord, array $prescription): EPrescription
    {
        $normalizedFrequency = AdherenceService::normalizeFrequency($prescription['frequency'] ?? null);
        $normalizedDuration = AdherenceService::normalizeDuration($prescription['duration'] ?? null);

        $issuedPrescription = EPrescription::create([
            'record_id' => $medicalRecord->record_id,
            'medication_name' => trim((string) $prescription['medication_name']),
            'dosage' => trim((string) $prescription['dosage']),
            'frequency' => $normalizedFrequency,
            'duration' => $normalizedDuration,
            'issued_at' => now(),
        ]);

        AdherenceService::createDoseScheduleForPrescription($issuedPrescription);

        return $issuedPrescription;
    }
}
