<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\SymptomLog;
use App\Models\AdherenceLog;
use App\Models\User;
use App\Services\AdherenceService;
use App\Services\AppointmentScheduleService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VeterinarianController extends Controller
{
    /**
     * Display the veterinarian dashboard
     */
    public function dashboard()
    {
        $vet = Auth::user();
        
        // Get emergency symptom logs
        $emergencies = SymptomLog::where('concern_level', 'emergency')->get();
        
        // Get high priority/triage cases
        $triage = SymptomLog::where('concern_level', 'emergency')->orWhere('concern_level', 'vet_visit')->get();

        // Get medication adherence metrics
        $prescriptions = EPrescription::with([
            'adherenceLogs' => fn ($q) => $q->orderByDesc('scheduled_datetime'),
        ])->get();

        $totalPrescriptions = $prescriptions->count();
        $prescriptionsWithLogs = $prescriptions->filter(fn ($p) => $p->adherenceLogs->isNotEmpty())->count();
        $adherenceRate = $totalPrescriptions > 0 
            ? round(($prescriptionsWithLogs / $totalPrescriptions) * 100, 2)
            : 0;

        $recentAppointments = Appointment::with('pet.owner')
            ->whereHas('consultation', fn ($query) => $query->where('veterinarian_id', $vet->id))
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(10)
            ->get();

        $recentMedicalRecords = MedicalRecord::with('pet.owner')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentPrescriptions = EPrescription::with('medicalRecord.pet.owner')
            ->orderByDesc('issued_at')
            ->limit(10)
            ->get();

        return view('veterinarian.dashboard', compact(
            'emergencies', 
            'triage',
            'adherenceRate',
            'totalPrescriptions',
            'recentAppointments',
            'recentMedicalRecords',
            'recentPrescriptions'
        ));
    }

    /**
     * Display appointment management
     */
    
    public function appointments(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $seeAll = $request->boolean('see_all');
        $appointmentScope = function ($query) {
            $query->where(function ($query) {
                $query->whereHas('consultation', function ($consultationQuery) {
                    $consultationQuery->where('veterinarian_id', Auth::id());
                })->orWhereDoesntHave('consultation');
            });
        };
        $searchScope = function ($query) use ($search) {
            if ($search === '') {
                return;
            }

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('reason_for_visit', 'like', "%{$search}%")
                    ->orWhere('consultation_mode', 'like', "%{$search}%")
                    ->orWhereHas('pet', function ($petQuery) use ($search) {
                        $petQuery->where('pet_name', 'like', "%{$search}%")
                            ->orWhere('species', 'like', "%{$search}%")
                            ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('full_name', 'like', "%{$search}%"));
                    });
            });
        };

        $pendingAppointments = Appointment::with(['pet.owner'])
            ->where($appointmentScope)
            ->where($searchScope)
            ->where('status', 'Pending')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->when(!$seeAll, fn ($query) => $query->limit(10))
            ->get();

        $approvedAppointments = Appointment::with(['pet.owner'])
            ->where($appointmentScope)
            ->where($searchScope)
            ->where('status', 'Approved')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->when(!$seeAll, fn ($query) => $query->limit(10))
            ->get();

        $totalPendingAppointments = Appointment::where($appointmentScope)
            ->where($searchScope)
            ->where('status', 'Pending')
            ->count();

        $totalApprovedAppointments = Appointment::where($appointmentScope)
            ->where($searchScope)
            ->where('status', 'Approved')
            ->count();

        return view('veterinarian.appointments', compact('pendingAppointments', 'approvedAppointments', 'totalPendingAppointments', 'totalApprovedAppointments', 'search', 'seeAll'));
    }

    /**
     * Display medical records
     */
    public function medicalRecords(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $seeAll = $request->boolean('see_all');

        $recordsQuery = MedicalRecord::with([
            'pet.owner',
            'prescriptions' => fn ($query) => $query->orderByDesc('issued_at'),
        ])->when($search !== '', function ($query) use ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('diagnosis', 'like', "%{$search}%")
                    ->orWhere('treatment_plan', 'like', "%{$search}%")
                    ->orWhere('vaccination_notes', 'like', "%{$search}%")
                    ->orWhereHas('pet', function ($petQuery) use ($search) {
                        $petQuery->where('pet_name', 'like', "%{$search}%")
                            ->orWhere('species', 'like', "%{$search}%")
                            ->orWhere('breed', 'like', "%{$search}%")
                            ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('full_name', 'like', "%{$search}%"));
                    });
            });
        })->orderByDesc('created_at');

        $allMedicalRecords = (clone $recordsQuery)->get();
        $medicalRecords = (clone $recordsQuery)
            ->when(!$seeAll, fn ($query) => $query->limit(10))
            ->get();

        $owners = User::whereHas('pets.medicalRecords')
            ->with(['pets' => fn ($query) => $query->whereHas('medicalRecords')->orderBy('pet_name')])
            ->orderBy('full_name')
            ->get();

        $patients = $owners->flatMap->pets->unique('pet_id')->values();
        $requestedPatientId = (int) ($request->query('pet') ?: $request->query('patient'));
        $selectedPetFromPatientParam = $requestedPatientId
            ? $patients->firstWhere('pet_id', $requestedPatientId)
            : null;

        $selectedOwner = $selectedPetFromPatientParam?->owner
            ?? ($request->query('owner')
                ? $owners->firstWhere('user_id', (int) $request->query('owner'))
                : null);

        $ownerPets = $selectedOwner?->pets ?? collect();
        $selectedPet = $selectedPetFromPatientParam
            ?? ($request->query('pet')
                ? $ownerPets->firstWhere('pet_id', (int) $request->query('pet'))
                : null);
        $selectedPatient = $selectedPet;

        $selectedPatientRecords = $selectedPet
            ? $allMedicalRecords->where('pet_id', $selectedPet->pet_id)->values()
            : collect();
        $selectedPatientRecordsPreview = $seeAll ? $selectedPatientRecords : $selectedPatientRecords->take(10);

        $selectedPatientPrescriptionCount = $selectedPatientRecords->sum(
            fn (MedicalRecord $record) => $record->prescriptions->count()
        );

        return view('veterinarian.medical-records', compact(
            'medicalRecords',
            'allMedicalRecords',
            'owners',
            'selectedOwner',
            'ownerPets',
            'selectedPet',
            'patients',
            'selectedPatient',
            'selectedPatientRecords',
            'selectedPatientRecordsPreview',
            'selectedPatientPrescriptionCount',
            'search',
            'seeAll'
        ));
    }

    /**
     * Display e-prescription module
     */
    public function prescriptions(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $seeAll = $request->boolean('see_all');

        $medicalRecords = MedicalRecord::with([
            'pet.owner',
            'prescriptions' => fn ($query) => $query->orderByDesc('issued_at'),
        ])->when($search !== '', function ($query) use ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('pet', function ($petQuery) use ($search) {
                        $petQuery->where('pet_name', 'like', "%{$search}%")
                            ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('full_name', 'like', "%{$search}%"));
                    });
            });
        })->orderByDesc('created_at')->get();

        $selectedRecord = $medicalRecords->firstWhere('record_id', (int) $request->query('record'));

        if (!$selectedRecord) {
            $selectedRecord = $medicalRecords->first();
        }

        $prescriptionsQuery = EPrescription::with('medicalRecord.pet.owner')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('medication_name', 'like', "%{$search}%")
                        ->orWhere('dosage', 'like', "%{$search}%")
                        ->orWhere('frequency', 'like', "%{$search}%")
                        ->orWhere('duration', 'like', "%{$search}%")
                        ->orWhereHas('medicalRecord.pet', function ($petQuery) use ($search) {
                            $petQuery->where('pet_name', 'like', "%{$search}%")
                                ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('full_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->orderByDesc('issued_at');

        $totalPrescriptionRows = (clone $prescriptionsQuery)->count();
        $prescriptions = (clone $prescriptionsQuery)
            ->when(!$seeAll, fn ($query) => $query->limit(10))
            ->get();

        $patientCount = $medicalRecords->pluck('pet_id')->unique()->count();
        $issuedTodayCount = EPrescription::whereDate('issued_at', today())->count();

        return view('veterinarian.prescriptions', compact('medicalRecords', 'selectedRecord', 'prescriptions', 'patientCount', 'issuedTodayCount', 'totalPrescriptionRows', 'search', 'seeAll'));
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
     * Update an existing e-prescription.
     */
    public function updatePrescription(Request $request, EPrescription $prescription)
    {
        $validator = Validator::make($request->all(), [
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
        $prescription->update([
            'medication_name' => trim((string) $validated['medication_name']),
            'dosage' => trim((string) $validated['dosage']),
            'frequency' => AdherenceService::normalizeFrequency($validated['frequency']),
            'duration' => AdherenceService::normalizeDuration($validated['duration']),
        ]);

        return redirect()->route('vet.prescriptions', ['record' => $prescription->record_id])
            ->with('success', 'E-prescription updated successfully.');
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

        AdherenceLog::create([
            'prescription_id' => $prescription->prescription_id,
            'scheduled_datetime' => $validated['scheduled_datetime'],
            'intake_status' => $validated['intake_status'],
            'confirmation_time' => $validated['confirmation_time'],
            'remarks' => $validated['remarks'],
        ]);

        return back()->with('success', 'Medication adherence logged successfully.');
    }

    /**
     * Show create appointment form
     */
    public function createAppointment()
    {
        $pets = Pet::with('owner')->get();
        $unavailablePetIds = Appointment::query()->activeEntries()->pluck('pet_id')->all();
        $bookedAppointments = AppointmentScheduleService::bookedAppointments();

        return view('veterinarian.create-appointment', compact('pets', 'unavailablePetIds', 'bookedAppointments'));
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

        return redirect()->route('vet.appointments')->with('success', 'Appointment created successfully!');
    }

    /**
     * Display the live appointment session workspace
     */
    public function showAppointmentSession(Appointment $appointment)
    {
        $appointment->loadMissing('pet.owner');

        $consultation = $this->resolveAppointmentConsultation($appointment);

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

        return redirect()
            ->route('vet.appointments.session', $appointment)
            ->with('success', 'Appointment session saved successfully.');
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

        $appointment->update(['status' => 'Missed']);

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
