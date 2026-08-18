<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\EPrescription;
use App\Models\MedicalRecord;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\AdherenceNotification;
use App\Models\AdherenceLog;
use App\Services\AdherenceService;
use App\Services\AppointmentScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PetOwnerController extends Controller
{
    private function onlineBookingEnabled(): bool
    {
        return (bool) SystemSetting::getValue('online_booking_enabled', true);
    }

    /**
     * Display the pet owner dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $pets = $user->pets()->get();
        $upcomingAppointments = Appointment::whereHas('pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->whereNotIn('status', ['Cancelled', 'Completed', 'Missed'])->orderBy('appointment_date')->take(4)->get();
        
        $prescriptions = EPrescription::whereHas('medicalRecord.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with(['medicalRecord.pet.owner', 'medicalRecord.consultation.veterinarian'])
            ->orderByDesc('issued_at')
            ->get();

        $medicalRecords = MedicalRecord::whereHas('pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with(['pet', 'consultation.veterinarian', 'prescriptions'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $activeReminders = AdherenceNotification::where('user_id', $user->user_id)
            ->where('status', 'Pending')
            ->where('scheduled_at', '<=', now()->addDay())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return view('pet-owner.dashboard', compact('pets', 'upcomingAppointments', 'prescriptions', 'medicalRecords', 'activeReminders'));
    }

    /**
     * Display user's pets
     */
    public function pets()
    {
        $user = Auth::user();
        $pets = $user->pets()->with('medicalRecords')->get();
        
        return view('pet-owner.pets', compact('pets'));
    }

    /**
     * Show create pet form
     */
    public function createPet()
    {
        return view('pet-owner.create-pet');
    }

    /**
     * Store new pet in database
     */
    public function storePet(Request $request)
    {
        if ($request->filled('name') && !$request->filled('pet_name')) {
            $request->merge(['pet_name' => $request->input('name')]);
        }

        $validated = $request->validate([
            'pet_name' => 'required|string|max:80',
            'species' => 'required|string|max:50',
            'breed' => 'required|string|max:80',
            'date_of_birth' => 'nullable|date',
            'age' => 'nullable|integer|min:0',
            'weight' => 'required|numeric|min:0.1',
            'sex' => 'required|string|max:20',
        ]);

        if (!empty($validated['age']) && empty($validated['date_of_birth'])) {
            $validated['date_of_birth'] = now()->subYears((int) $validated['age'])->toDateString();
        }
        unset($validated['age']);

        $user = Auth::user();
        $pet = Pet::create([
            'user_id' => $user->user_id,
            'pet_name' => $validated['pet_name'],
            'species' => $validated['species'],
            'breed' => $validated['breed'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'weight' => $validated['weight'],
            'sex' => $validated['sex'],
        ]);

        return redirect()->route('pet-owner.pets')->with('success', 'Pet added successfully!');
    }

    /**
     * Display AI symptom checker
     */
    public function symptomChecker()
    {
        $user = Auth::user();
        $pets = $user->pets()->get();
        
        return view('pet-owner.symptom-checker', compact('pets'));
    }

    /**
     * Display appointments
     */
   
public function appointments()
{
    $user = Auth::user();
    $userAppointments = Appointment::whereHas('pet', function ($query) use ($user) {
        $query->where('user_id', $user->user_id);
    })->with('pet')->get();

    $today = now()->startOfDay();

    // Upcoming appointments (future and not cancelled/completed/missed)
    $upcomingAppointments = $userAppointments
        ->filter(fn (Appointment $appointment) => $appointment->appointment_date && $appointment->appointment_date->greaterThanOrEqualTo($today))
        ->whereNotIn('status', ['Cancelled', 'Completed', 'Missed'])
        ->sortBy('appointment_date')
        ->take(5);  // ← Add this

    // Missed appointments (explicitly marked missed or old unfinished appointments)
    $missedAppointments = $userAppointments
        ->filter(function (Appointment $appointment) use ($today) {
            if ($appointment->status === 'Missed') {
                return true;
            }

            return $appointment->appointment_date
                && $appointment->appointment_date->lessThan($today)
                && !in_array($appointment->status, ['Cancelled', 'Completed'], true);
        })
        ->sortByDesc('appointment_date')
        ->take(5);  // ← Add this
    
    // Completed appointments
    $completedAppointments = $userAppointments->where('status', 'Completed')
        ->sortByDesc('appointment_date')
        ->take(5);  // ← Add this
    
    $pets = $user->pets()->get();
    $calendarBookings = AppointmentScheduleService::calendarBookings();
    $calendarSlots = AppointmentScheduleService::WORKING_HOURS;
    $onlineBookingEnabled = $this->onlineBookingEnabled();
    
    return view('pet-owner.appointments', compact('upcomingAppointments', 'missedAppointments', 'completedAppointments', 'pets', 'calendarBookings', 'calendarSlots', 'onlineBookingEnabled'));
}

    /**
     * Display prescriptions and medications
     */
    public function prescriptions()
    {
        $user = Auth::user();

        AdherenceService::markExpiredPendingDosesForUser($user->user_id);
        AdherenceService::ensureNextPendingDoseNotificationForUser($user->user_id);

        $prescriptions = EPrescription::whereHas('medicalRecord.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with([
            'medicalRecord.pet.owner',
            'medicalRecord.consultation.veterinarian',
            'adherenceLogs' => fn ($query) => $query->with('notification')->orderBy('scheduled_datetime'),
        ])->get()
            ->sortBy([
                fn ($left, $right) => (int) !$left->adherenceLogs->contains('intake_status', 'Pending')
                    <=> (int) !$right->adherenceLogs->contains('intake_status', 'Pending'),
                fn ($left, $right) => ($left->issued_at?->timestamp ?? 0) <=> ($right->issued_at?->timestamp ?? 0),
                fn ($left, $right) => $left->prescription_id <=> $right->prescription_id,
            ])
            ->values();

        return view('pet-owner.prescriptions', compact('prescriptions'));
    }

    /**
     * Display the pet owner's medical records
     */
    public function medicalRecords()
    {
        $user = Auth::user();

        $medicalRecords = MedicalRecord::whereHas('pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with([
            'pet.owner',
            'consultation.veterinarian',
            'prescriptions.adherenceLogs',
        ])->orderByDesc('updated_at')->get();

        return view('pet-owner.medical-records', compact('medicalRecords'));
    }

    /**
     * Display a single medical record with linked prescriptions.
     */
    public function medicalRecordDetails(MedicalRecord $record)
    {
        $record->load([
            'pet.owner',
            'consultation.veterinarian',
            'prescriptions.adherenceLogs.notification',
        ]);

        abort_unless($record->pet?->user_id === Auth::user()->user_id, 403, 'Unauthorized');

        return view('pet-owner.medical-record-details', [
            'record' => $record,
        ]);
    }

    /**
     * Display notifications center with merged adherence and appointment notifications
     */
    public function notifications(Request $request)
    {
        $user = Auth::user();
        $filter = $request->query('filter', 'all'); // all, appointments, adherence

        // Fetch appointment notifications from the new appointment_notifications table
        $appointmentNotifications = AppointmentNotification::where('notification_status', '!=', 'deleted')
            ->with(['appointment.pet.owner'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($notif) use ($user) {
                try {
                    // Skip if appointment or relationships are missing
                    if (!$notif->appointment || !$notif->appointment->pet || !$notif->appointment->pet->owner) {
                        \Log::info('Notification missing relationships', ['notif_id' => $notif->id]);
                        return false;
                    }
                    $ownerUserId = (int) $notif->appointment->pet->owner->user_id;
                    $currentUserId = (int) $user->user_id;
                    $match = $ownerUserId === $currentUserId;
                    if (!$match) {
                        \Log::info('User ID mismatch', ['owner_id' => $ownerUserId, 'current_id' => $currentUserId]);
                    }
                    return $match;
                } catch (\Exception $e) {
                    \Log::error('Error filtering appointment notification', ['error' => $e->getMessage(), 'notif_id' => $notif->id ?? 'unknown']);
                    return false;
                }
            })
            ->unique(fn ($notif) => $notif->appointment_id . '|' . $notif->notification_status)
            ->map(function ($notif) {
                try {
                    return (object)[
                        'id' => "appt-notif-{$notif->id}",
                        'notification_record_id' => $notif->id,
                        'appointment_id' => $notif->appointment->appointment_id,
                        'notification_status' => $notif->notification_status,
                        'type' => 'appointment',
                        'message' => $this->formatAppointmentMessageFromAppointment($notif->appointment, $notif->notification_status),
                        'pet_name' => $notif->appointment->pet->pet_name ?? 'Your Pet',
                        'scheduled_at' => $notif->created_at,
                        'created_at' => $notif->created_at,
                        'timestamp_display' => $this->formatTimestamp($notif->created_at),
                        'raw_appointment' => $notif->appointment,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error mapping appointment notification', ['error' => $e->getMessage(), 'notif_id' => $notif->id]);
                    return null;
                }
            })
            ->filter(fn ($item) => $item !== null)
            ->values();

        // Fetch adherence notifications (scheduled_at <= now)
        $adherenceNotifications = AdherenceNotification::where('user_id', $user->user_id)
            ->where('status', '!=', 'Deleted')
            ->where('scheduled_at', '<=', now())
            ->with(['adherenceLog.prescription.medicalRecord.pet'])
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn ($notif) => (object)[
                'id' => "adh-{$notif->notification_id}",
                'notification_id' => $notif->notification_id,
                'adherence_id' => $notif->adherence_id,
                'type' => 'adherence',
                'status' => $notif->status,
                'pet_name' => $notif->adherenceLog?->prescription?->medicalRecord?->pet?->pet_name ?? 'Your Pet',
                'medication_name' => $notif->medication_name ?? 'Medication',
                'dosage' => $notif->dosage ?? 'N/A',
                'frequency' => $notif->adherenceLog?->prescription?->frequency ?? 'N/A',
                'message' => "{$notif->medication_name} ({$notif->dosage})",
                'scheduled_at' => $notif->scheduled_at,
                'confirmation_deadline' => $notif->confirmation_deadline,
                'created_at' => $notif->created_at,
                'timestamp_display' => $this->formatTimestamp($notif->scheduled_at),
                'raw_notification' => $notif,
            ]);

        // Filter based on type
        if ($filter === 'appointments') {
            $notifications = $appointmentNotifications;
        } elseif ($filter === 'adherence') {
            $notifications = $adherenceNotifications;
        } else {
            // Merge and sort by created_at descending
            $notifications = collect(array_merge($appointmentNotifications->all(), $adherenceNotifications->all()))
                ->sortByDesc('created_at')
                ->values();
        }

        // Paginate
        $per_page = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $notifications = collect($notifications)
            ->slice(($page - 1) * $per_page, $per_page)
            ->values();

        // Fetch adherence records for datatable
        $adherenceRecords = collect();
        if ($filter === 'adherence' || $filter === 'all') {
            $adherenceRecords = AdherenceLog::whereHas('prescription.medicalRecord.pet', function ($query) use ($user) {
                $query->where('user_id', $user->user_id);
            })
            ->with(['prescription.medicalRecord.pet'])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        return view('pet-owner.notifications', [
            'notifications' => $notifications,
            'adherenceRecords' => $adherenceRecords,
            'appointmentCount' => count($appointmentNotifications),
            'adherenceCount' => count($adherenceNotifications),
            'filter' => $filter,
            'totalCount' => count($appointmentNotifications) + count($adherenceNotifications),
            'per_page' => $per_page,
            'current_page' => $page,
        ]);
    }

    /**
     * Delete a single notification (soft delete)
     */
    public function deleteNotification(Request $request)
    {
        $user = Auth::user();
        $type = $request->query('type'); // 'appointment' or 'adherence'
        $id = $request->query('id');

        if ($type === 'appointment') {
            // Remove 'appt-notif-' prefix from id to get the notification record ID
            $notificationRecordId = str_replace('appt-notif-', '', $id);
            
            $notification = AppointmentNotification::findOrFail($notificationRecordId);
            
            // Verify that this appointment belongs to the user
            $appointment = $notification->appointment;
            if ($appointment->pet->owner->user_id !== $user->user_id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            // Soft delete by marking status
            $notification->update(['notification_status' => 'deleted']);
            return response()->json(['message' => 'Notification deleted successfully'], 200);
        } elseif ($type === 'adherence') {
            // Remove 'adh-' prefix from id
            $notificationId = str_replace('adh-', '', $id);
            $notification = AdherenceNotification::where('notification_id', $notificationId)
                ->where('user_id', $user->user_id)
                ->firstOrFail();
            
            // Soft delete
            $notification->deleteNotification();
            return response()->json(['message' => 'Notification deleted successfully'], 200);
        }

        return response()->json(['error' => 'Invalid notification type'], 400);
    }

    /**
     * Clear all notifications (soft delete)
     */
    public function clearAllNotifications(Request $request)
    {
        $user = Auth::user();
        
        // Soft delete all appointment notifications for this user
        AppointmentNotification::whereHas('appointment.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })
        ->where('notification_status', '!=', 'deleted')
        ->update(['notification_status' => 'deleted']);
        
        // Soft delete all adherence notifications
        AdherenceNotification::where('user_id', $user->user_id)
            ->where('status', '!=', 'Deleted')
            ->update(['status' => 'Deleted']);
        
        return response()->json(['message' => 'All notifications cleared successfully'], 200);
    }

    /**
     * Format appointment notification message
     */
    private function formatAppointmentMessageFromAppointment($appointment, ?string $notificationStatus = null)
    {
        try {
            // Get client name and pet name
            $clientName = $appointment->pet->owner->first_name ?? 'Client';
            $petName = $appointment->pet->pet_name ?? 'Pet';
            $status = $notificationStatus ?? $appointment->status ?? 'Pending';
            $date = Carbon::parse($appointment->appointment_date)->format('F d, Y');
            
            // Determine greeting based on time
            $hour = now()->hour;
            if ($hour < 12) {
                $greeting = 'Good Morning';
            } elseif ($hour < 18) {
                $greeting = 'Good Afternoon';
            } else {
                $greeting = 'Good Evening';
            }
            
            // Handle different status messages
            if (strtolower($status) === 'completed') {
                return "{$greeting} {$clientName}, thank you for visiting us! {$petName}'s appointment on {$date} has been completed. We look forward to seeing you and {$petName} again soon.";
            }
            
            if (strtolower($status) === 'missed') {
                return "{$greeting} {$clientName}, you missed your scheduled appointment for {$petName} on {$date}. Please reschedule at your earliest convenience.";
            }

            if (strtolower($status) === 'cancelled') {
                return "{$greeting} {$clientName}, {$petName}'s appointment on {$date} has been cancelled. Please reschedule if needed.";
            }
            
            // Format status for display
            $statusDisplay = strtolower($status);
            
            return "{$greeting} {$clientName}, {$petName} is {$statusDisplay} for appointment on {$date}";
        } catch (\Exception $e) {
            \Log::error('Error formatting appointment message', ['error' => $e->getMessage()]);
            return 'Appointment notification';
        }
    }

    private function formatAppointmentMessage($notification)
    {
        try {
            // Get the appointment from related_reference
            $appointmentId = $notification->related_reference;
            
            if (!$appointmentId) {
                \Log::warning('No related_reference in notification', [
                    'notification_id' => $notification->reminder_id,
                    'reminder_type' => $notification->reminder_type
                ]);
                return 'Appointment notification';
            }
            
            $appointment = Appointment::with('pet.owner')->find($appointmentId);
            
            if (!$appointment) {
                \Log::warning('Appointment not found', [
                    'appointment_id' => $appointmentId
                ]);
                return 'Appointment notification';
            }
            
            return $this->formatAppointmentMessageFromAppointment($appointment);
        } catch (\Exception $e) {
            \Log::error('Error formatting appointment message', [
                'error' => $e->getMessage(),
                'notification' => $notification
            ]);
            // Fallback to generic message
            $types = [
                'appointment_reminder' => 'Appointment Reminder',
                'appointment_confirmed' => 'Appointment Confirmed',
                'appointment_cancelled' => 'Appointment Cancelled',
                'appointment_rescheduled' => 'Appointment Rescheduled',
                'appointment_completed' => 'Appointment Completed',
                'consultation_ready' => 'Consultation Ready',
            ];
            $type = $types[$notification->reminder_type] ?? ucwords(str_replace('_', ' ', $notification->reminder_type));
            return $type;
        }
    }

    /**
     * Format timestamp for display (e.g., "Just now", "5 minutes ago", "Today at 2:00 PM")
     */
    private function formatTimestamp(Carbon $time)
    {
        $now = Carbon::now();
        $diff = $now->diffInSeconds($time);

        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $minutes = $now->diffInMinutes($time);
            return $minutes == 1 ? '1 minute ago' : "{$minutes} minutes ago";
        }
        if ($diff < 86400) {
            $hours = $now->diffInHours($time);
            return $hours == 1 ? '1 hour ago' : "{$hours} hours ago";
        }
        if ($now->diffInDays($time) == 0) {
            return 'Today at ' . $time->format('g:i A');
        }
        if ($now->diffInDays($time) == 1) {
            return 'Yesterday at ' . $time->format('g:i A');
        }
        
        return $time->format('M d, Y g:i A');
    }

    /**
     * Show edit pet form
     */
    public function editPet($id)
    {
        $pet = Pet::findOrFail($id);
        Gate::authorize('update', $pet);
        
        return view('pet-owner.edit-pet', compact('pet'));
    }

    /**
     * Update pet in database
     */
    public function updatePet(Request $request, $id)
    {
        $pet = Pet::findOrFail($id);
        Gate::authorize('update', $pet);

        if ($request->filled('name') && !$request->filled('pet_name')) {
            $request->merge(['pet_name' => $request->input('name')]);
        }

        $validated = $request->validate([
            'pet_name' => 'required|string|max:80',
            'species' => 'required|string|max:50',
            'breed' => 'required|string|max:80',
            'date_of_birth' => 'nullable|date',
            'age' => 'nullable|integer|min:0',
            'weight' => 'required|numeric|min:0.1',
            'sex' => 'required|string|max:20',
        ]);

        if (!empty($validated['age']) && empty($validated['date_of_birth'])) {
            $validated['date_of_birth'] = now()->subYears((int) $validated['age'])->toDateString();
        }
        unset($validated['age']);

        $pet->update($validated);

        return redirect()->route('pet-owner.pets')->with('success', 'Pet updated successfully!');
    }

    /**
     * Show delete confirmation page
     */
    public function confirmDeletePet($id)
    {
        $pet = Pet::findOrFail($id);
        Gate::authorize('delete', $pet);
        
        return view('pet-owner.confirm-delete-pet', compact('pet'));
    }

    /**
     * Delete pet from database
     */
    public function deletePet($id)
    {
        $pet = Pet::findOrFail($id);
        Gate::authorize('delete', $pet);

        $petName = $pet->pet_name;
        $pet->delete();

        return redirect()->route('pet-owner.pets')->with('success', "Pet '{$petName}' has been deleted.");
    }

    /**
     * Show appointment booking form
     */
    public function bookAppointment()
    {
        $user = Auth::user();
        $pets = $user->pets()->get();
        $unavailablePetIds = Appointment::query()->activeEntries()->pluck('pet_id')->all();
        $calendarBookings = AppointmentScheduleService::calendarBookings();
        $calendarSlots = AppointmentScheduleService::WORKING_HOURS;
        $onlineBookingEnabled = $this->onlineBookingEnabled();

        return view('pet-owner.book-appointment', compact('pets', 'unavailablePetIds', 'calendarBookings', 'calendarSlots', 'onlineBookingEnabled'));
    }

    /**
     * Store appointment request from pet owner
     */
    public function storeAppointmentRequest(Request $request)
    {
        if (!$this->onlineBookingEnabled()) {
            return back()
                ->withInput()
                ->withErrors([
                    'booking' => 'Online booking is currently disabled by the clinic. Please contact the staff to schedule an appointment.',
                ]);
        }

        AppointmentScheduleService::normalizeDateTimeInput($request);

        if (!$request->filled('consultation_mode')) {
            $request->merge(['consultation_mode' => 'In-clinic']);
        }

        $reason = $request->input('reason_for_visit') ?? $request->input('reason');
        $request->merge(['reason' => $reason]);

        $validator = Validator::make($request->all(), [
            'pet_id' => 'required|exists:pets,pet_id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'consultation_mode' => 'required|in:In-clinic,Teleconsultation',
            'reason' => 'required|string',
        ]);
        $validator->after(fn ($validator) => AppointmentScheduleService::validateSlot($validator, $request));
        $validated = $validator->validate();

        $user = Auth::user();
        $pet = Pet::findOrFail($validated['pet_id']);
        
        // Verify pet belongs to user
        if ($pet->user_id !== $user->user_id) {
            abort(403, 'Unauthorized');
        }

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
            'status' => 'Pending',
            'proof_of_payment' => null,
        ]);

        // Create initial notification for Pending appointment
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, 'Pending');
        } catch (\Exception $e) {
            \Log::error('Error creating pending appointment notification', ['error' => $e->getMessage()]);
        }

        return redirect()->route('pet-owner.appointments')->with('success', 'Appointment request submitted! Staff will review and confirm shortly.');
    }

    /**
     * Get available times for a given date (AJAX endpoint)
     */
    public function getAvailableTimes(Request $request)
    {
        $date = $request->query('date');

        $allTimes = AppointmentScheduleService::WORKING_HOURS;

        // Remove booked times
        $bookedTimes = Appointment::query()
            ->activeEntries()
            ->whereDate('appointment_date', $date)
            ->get()
            ->map(fn (Appointment $appointment) => $appointment->appointment_time->format('H:i'))
            ->toArray();

        $availableTimes = array_diff($allTimes, $bookedTimes);

        return response()->json(['available_times' => array_values($availableTimes)]);
    }

    /**
     * Cancel a pending appointment
     */
    public function cancelAppointment($id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return redirect()->back()->with('error', 'Appointment not found');
        }

        // Authorize: only owner can cancel their own appointment and only if pending or approved
        if ($appointment->pet->user_id !== Auth::user()->user_id || !in_array($appointment->status, ['Pending', 'Approved'])) {
            return redirect()->back()->with('error', 'You can only cancel pending or confirmed appointments');
        }

        $appointment->update(['status' => 'Cancelled']);

        // Create notification for cancelled appointment
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, 'Cancelled');
        } catch (\Exception $e) {
            \Log::error('Error creating cancelled appointment notification', ['error' => $e->getMessage()]);
        }

        return redirect()->route('pet-owner.appointments')->with('success', 'Appointment cancelled successfully');
    }

    /**
     * Reschedule a pending appointment
     */
    public function rescheduleAppointment( Request $request, $id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return redirect()->back()->with('error', 'Appointment not found');
        }

        // Authorize: only owner can reschedule and only if pending or approved
        if ($appointment->pet->user_id !== Auth::user()->user_id || !in_array($appointment->status, ['Pending', 'Approved'])) {
            return redirect()->back()->with('error', 'You can only reschedule pending or confirmed appointments');
        }

        AppointmentScheduleService::normalizeDateTimeInput($request);

        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
        ]);
        $validator->after(fn ($validator) => AppointmentScheduleService::validateSlot($validator, $request, $appointment->appointment_id));
        $validated = $validator->validate();

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'status' => 'Pending',
        ]);

        // Create notification for rescheduled appointment
        try {
            AppointmentNotification::recordStatus($appointment->appointment_id, 'Rescheduled');
        } catch (\Exception $e) {
            \Log::error('Error creating rescheduled appointment notification', ['error' => $e->getMessage()]);
        }

        return redirect()->route('pet-owner.appointments')->with('success', 'Appointment rescheduled successfully. Please wait for staff approval of the new time.');
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notificationId)
    {
        $user = Auth::user();

        // Check if it's an adherence notification (id format: "adh-{id}")
        $adherenceNotif = AdherenceNotification::where('notification_id', $notificationId)
            ->where('user_id', $user->user_id)
            ->first();

        if ($adherenceNotif) {
            $adherenceNotif->update(['status' => 'Confirmed']);
            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        }

        // Check if it's an appointment notification
        $appointmentNotif = Notification::where('reminder_id', $notificationId)
            ->where('user_id', $user->user_id)
            ->first();

        if ($appointmentNotif) {
            $appointmentNotif->update(['sent_status' => 'sent']);
            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        }

        return response()->json(['success' => false, 'error' => 'Notification not found'], 404);
    }

    /**
     * Delete adherence record
     */
    public function deleteAdherence($adherenceId)
    {
        $user = Auth::user();

        // Verify ownership
        $adherence = AdherenceLog::whereHas('prescription.medicalRecord.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->findOrFail($adherenceId);

        // Delete associated notification first
        AdherenceNotification::where('adherence_id', $adherenceId)->delete();

        // Delete adherence record
        $adherence->delete();

        return response()->json(['success' => true, 'message' => 'Adherence record deleted successfully']);
    }

    /**
     * View adherence details (for modal)
     */
    public function viewAdherence($adherenceId)
    {
        $user = Auth::user();

        // Verify ownership
        $adherence = AdherenceLog::whereHas('prescription.medicalRecord.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })
        ->with(['prescription' => function ($q) {
            $q->with(['medicalRecord' => function ($q2) {
                $q2->with('pet');
            }]);
        }])
        ->findOrFail($adherenceId);

        return response()->json([
            'id' => $adherence->adherence_id,
            'pet_name' => $adherence->prescription->medicalRecord->pet->pet_name,
            'medication_name' => $adherence->prescription->medication_name,
            'dosage' => $adherence->prescription->dosage,
            'frequency' => $adherence->prescription->frequency,
            'status' => $adherence->intake_status,
            'scheduled_date' => $adherence->scheduled_datetime?->format('M d, Y • h:i A') ?? 'N/A',
            'confirmation_deadline' => $adherence->confirmation_deadline?->format('M d, Y • h:i A') ?? 'N/A',
            'remarks' => $adherence->remarks ?? 'No remarks',
        ]);
    }

    /**
     * Save notification preferences
     */
    public function saveNotificationPreferences(Request $request)
    {
        $user = Auth::user();

        $preferences = [
            'medication_reminders' => $request->has('medication_reminders'),
            'appointment_alerts' => $request->has('appointment_alerts'),
            'vet_messages' => $request->has('vet_messages'),
            'promotions' => $request->has('promotions'),
        ];

        // Save to user's notification preferences (if you have a preferences column in users table)
        // For now, we'll just acknowledge the save
        session(['notification_preferences' => $preferences]);

        return redirect()->route('pet-owner.notifications')->with('success', 'Notification preferences saved successfully!');
    }
}
