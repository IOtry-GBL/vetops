<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Appointment;
use App\Models\EPrescription;
use App\Services\AppointmentScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class PetOwnerController extends Controller
{
    /**
     * Display the pet owner dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $pets = $user->pets()->get();
        $appointmentQuery = Appointment::whereHas('pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with('pet');

        $upcomingAppointments = (clone $appointmentQuery)
            ->whereNotIn('status', ['Cancelled', 'Completed', 'Missed'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->take(4)
            ->get();

        $appointmentSchedules = (clone $appointmentQuery)
            ->whereNotIn('status', ['Cancelled', 'Completed', 'Missed'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $processedAppointments = (clone $appointmentQuery)
            ->whereIn('status', ['Completed', 'Cancelled', 'Missed'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();
        
        $prescriptions = EPrescription::whereHas('medicalRecord.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with(['medicalRecord.pet.owner', 'medicalRecord.consultation.veterinarian'])
            ->orderByDesc('issued_at')
            ->get();

        return view('pet-owner.dashboard', compact('pets', 'upcomingAppointments', 'appointmentSchedules', 'processedAppointments', 'prescriptions'));
    }

    /**
     * Display user's pets
     */
    public function pets()
    {
        $user = Auth::user();
        $pets = $user->pets()->with(['medicalRecords', 'appointments'])->get();
        
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
    public function storePet(\Illuminate\Http\Request $request)
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
            ->sortBy('appointment_date');

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
            ->sortByDesc('appointment_date');
        
        // Completed appointments
        $completedAppointments = $userAppointments->where('status', 'Completed')
            ->sortByDesc('appointment_date');
        
        $pets = $user->pets()->get();
        
        $bookedAppointments = AppointmentScheduleService::bookedAppointments();

        return view('pet-owner.appointments', compact('upcomingAppointments', 'missedAppointments', 'completedAppointments', 'pets', 'bookedAppointments'));
    }

    /**
     * Display prescriptions and medications
     */
    public function prescriptions()
    {
        $user = Auth::user();
        $prescriptions = EPrescription::whereHas('medicalRecord.pet', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with([
            'medicalRecord.pet.owner',
            'medicalRecord.consultation.veterinarian',
            'adherenceLogs' => fn ($query) => $query->with('notification')->orderBy('scheduled_datetime'),
        ])->orderByDesc('issued_at')->get();
        
        return view('pet-owner.prescriptions', compact('prescriptions'));
    }

    /**
     * Display notifications center
     */
    public function notifications()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();
        
        return view('pet-owner.notifications', compact('notifications'));
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
    public function updatePet(\Illuminate\Http\Request $request, $id)
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
        $bookedAppointments = AppointmentScheduleService::bookedAppointments();

        return view('pet-owner.book-appointment', compact('pets', 'unavailablePetIds', 'bookedAppointments'));
    }

    /**
     * Store appointment request from pet owner
     */
    public function storeAppointmentRequest(\Illuminate\Http\Request $request)
    {
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
        $validator->after(function ($validator) use ($request) {
            if ($request->filled('pet_id') && Appointment::petHasActiveEntry((int) $request->input('pet_id'))) {
                return;
            }

            AppointmentScheduleService::validateSlot($validator, $request);
        });
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

        Appointment::create([
            'pet_id' => $validated['pet_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'consultation_mode' => $validated['consultation_mode'],
            'reason_for_visit' => $reason,
            'status' => 'Pending',
            'proof_of_payment' => null,
        ]);

        return redirect()->route('pet-owner.appointments')->with('success', 'Appointment request submitted! Staff will review and confirm shortly.');
    }

    /**
     * Get available times for a given date (AJAX endpoint)
     */
    public function getAvailableTimes(\Illuminate\Http\Request $request)
    {
        $date = $request->query('date');
        
        // Get all appointments for this date
        $appointmentsOnDate = Appointment::whereDate('appointment_date', $date)->get();
        
        // Generate time slots (30-minute intervals from 08:00 to 18:00)
        $allTimes = [];
        for ($hour = 8; $hour < 18; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $allTimes[] = sprintf('%02d:%02d', $hour, $minute);
            }
        }

        // Remove booked times
        $bookedTimes = $appointmentsOnDate->map(function ($apt) {
            return $apt->appointment_date->format('H:i');
        })->toArray();

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
        if ($appointment->pet->user_id !== Auth::id() || !in_array($appointment->status, ['Pending', 'Approved'])) {
            return redirect()->back()->with('error', 'You can only cancel pending or confirmed appointments');
        }

        $appointment->update(['status' => 'Cancelled']);

        return redirect()->route('pet-owner.appointments')->with('success', 'Appointment cancelled successfully');
    }

    /**
     * Reschedule a pending appointment
     */
    public function rescheduleAppointment(\Illuminate\Http\Request $request, $id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return redirect()->back()->with('error', 'Appointment not found');
        }

        // Authorize: only owner can reschedule and only if pending or approved
        if ($appointment->pet->user_id !== Auth::id() || !in_array($appointment->status, ['Pending', 'Approved'])) {
            return redirect()->back()->with('error', 'You can only reschedule pending or confirmed appointments');
        }

        AppointmentScheduleService::normalizeDateTimeInput($request);

        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
        ]);
        $validator->after(fn ($validator) => AppointmentScheduleService::validateSlot($validator, $request, $appointment->id));
        $validated = $validator->validate();

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
        ]);

        return redirect()->route('pet-owner.appointments')->with('success', 'Appointment rescheduled successfully');
    }
}
