<?php

namespace App\Http\Controllers;

use App\Models\AdherenceLog;
use App\Models\AdherenceNotification;
use App\Models\EPrescription;
use App\Services\AdherenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AdherenceController extends Controller
{
    /**
     * Display adherence dashboard for pet owners
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        $pendingNotifications = AdherenceNotification::where('user_id', $user->user_id)
            ->activeWindow()
            ->count();

        $pendingNotificationsList = AdherenceNotification::where('user_id', $user->user_id)
            ->activeWindow()
            ->with(['adherenceLog.prescription.medicalRecord.pet'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $upcomingNotificationsList = AdherenceNotification::where('user_id', $user->user_id)
            ->where('status', 'Pending')
            ->where('scheduled_at', '>', now())
            ->with(['adherenceLog.prescription.medicalRecord.pet'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $recentMissed = AdherenceNotification::where('user_id', $user->user_id)
            ->where('status', 'Missed')
            ->whereDate('scheduled_at', '>=', now()->subDays(30))
            ->count();

        $confirmationRate = $this->getConfirmationRate($user->user_id);

        return view('pet-owner.adherence-dashboard', [
            'pendingNotifications' => $pendingNotifications,
            'pendingNotificationsList' => $pendingNotificationsList,
            'upcomingNotificationsList' => $upcomingNotificationsList,
            'recentMissed' => $recentMissed,
            'confirmationRate' => $confirmationRate,
        ]);
    }

    /**
     * Create adherence reminders for scheduled prescriptions
     */
    public function createReminder(Request $request)
    {
        $request->validate([
            'adherence_id' => 'required|exists:adherence_logs,adherence_id',
        ]);

        $adherenceLog = AdherenceLog::findOrFail($request->adherence_id);
        $prescription = $adherenceLog->prescription;
        $user = $prescription->medicalRecord->pet->owner;

        $scheduledTime = Carbon::parse($adherenceLog->scheduled_datetime);
        $deadlineTime = AdherenceService::buildConfirmationDeadline($scheduledTime);

        // Update adherence log with confirmation deadline
        $adherenceLog->update([
            'confirmation_deadline' => $deadlineTime,
            'is_notified' => true,
        ]);

        // Use updateOrCreate to prevent race condition and duplicate notifications
        $notification = AdherenceNotification::updateOrCreate(
            ['adherence_id' => $adherenceLog->aherence_id],
            [
                'user_id' => $user->user_id,
                'medication_name' => $prescription->medication_name,
                'dosage' => $prescription->dosage,
                'scheduled_at' => $scheduledTime,
                'confirmation_deadline' => $deadlineTime,
                'status' => 'Pending',
            ]
        );

        // Send email notification to user
        try {
            Mail::send('emails.adherence-reminder', [
                'user' => $user,
                'medication_name' => $prescription->medication_name,
                'dosage' => $prescription->dosage,
                'scheduled_at' => $scheduledTime->timezone(AdherenceService::clinicTimezone()),
                'deadline' => $deadlineTime->timezone(AdherenceService::clinicTimezone()),
            ], function ($message) use ($user, $prescription) {
                $message->to($user->email)
                    ->subject('Medication Reminder - ' . ($prescription->medication_name ?? 'Medication Due'));
            });
        } catch (\Exception $e) {
            // Log email error but don't fail the reminder creation
            Log::error('Adherence reminder email failed for user ' . $user->user_id . ': ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Reminder created successfully',
            'notification' => $notification,
        ]);
    }

    /**
     * Confirm adherence via API or form
     */
    public function confirmAdherence(Request $request, $notificationId)
    {
        $notification = AdherenceNotification::findOrFail($notificationId);

        // Verify user authorization
        if ($notification->user_id !== Auth::user()->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if already confirmed or missed
        if ($notification->status !== 'Pending') {
            return response()->json(['error' => 'This notification is no longer pending'], 400);
        }

        if (now()->lt($notification->scheduled_at)) {
            return response()->json(['error' => 'Confirmation is not available until the scheduled medication time.'], 400);
        }

        // Check if deadline has passed
        if (now()->greaterThan($notification->confirmation_deadline)) {
            $notification->markAsMissed();
            return response()->json(['error' => 'Confirmation window has expired'], 400);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Confirm adherence
        if ($notification->confirm($validated['notes'] ?? null)) {
            $notification->refresh();

            return response()->json([
                'message' => 'Adherence confirmed successfully',
                'notification' => $notification->toArray(),
            ]);
        }

        return response()->json(['error' => 'Unable to confirm adherence'], 400);
    }

    /**
     * Confirm a scheduled dose directly from the prescription screen
     */
    public function confirmDose(Request $request, AdherenceLog $adherenceLog)
    {
        $adherenceLog->loadMissing('prescription.medicalRecord.pet.owner', 'notification');

        $owner = $adherenceLog->prescription?->medicalRecord?->pet?->owner;

        if (!$owner || (int) $owner->user_id !== (int) Auth::user()->user_id) {
            abort(403, 'Unauthorized');
        }

        if ($adherenceLog->intake_status === 'Taken') {
            return back()->with('success', 'This dose was already confirmed.');
        }

        if ($adherenceLog->isUpcoming()) {
            $scheduledAt = $adherenceLog->scheduledDatetimeInClinicTimezone();

            return back()->with(
                'error',
                'This dose can be confirmed starting at ' . ($scheduledAt ? $scheduledAt->format('g:i A') . ' PH' : 'the scheduled time') . '.'
            );
        }

        if ($adherenceLog->isExpiredForConfirmation()) {
            AdherenceService::markDoseAsMissed($adherenceLog);

            return back()->with('error', 'The confirmation window has expired for this dose.');
        }

        if (!$adherenceLog->isAvailableForConfirmation()) {
            return back()->with('error', 'This dose is no longer pending confirmation.');
        }

        AdherenceService::markExpiredPendingDosesForPrescription($adherenceLog->prescription);

        $confirmedAt = now();
        $deadline = $adherenceLog->resolvedConfirmationDeadline();

        $adherenceLog->update([
            'confirmation_deadline' => $deadline,
            'intake_status' => 'Taken',
            'confirmation_time' => $confirmedAt,
            'is_notified' => true,
        ]);

        $notification = $adherenceLog->notification;

        if ($notification) {
            $notification->update([
                'scheduled_at' => $adherenceLog->scheduled_datetime,
                'confirmation_deadline' => $deadline,
                'status' => 'Confirmed',
                'confirmed_at' => $confirmedAt,
            ]);
        } else {
            AdherenceNotification::create([
                'user_id' => $owner->user_id,
                'adherence_id' => $adherenceLog->adherence_id,
                'medication_name' => $adherenceLog->prescription->medication_name,
                'dosage' => $adherenceLog->prescription->dosage,
                'scheduled_at' => $adherenceLog->scheduled_datetime,
                'confirmation_deadline' => $deadline,
                'status' => 'Confirmed',
                'confirmed_at' => $confirmedAt,
            ]);
        }

        return back()->with('success', 'Dose confirmed successfully.');
    }

    /**
     * Snooze a notification for later
     */
    public function snoozeReminder(Request $request, $notificationId)
    {
        $request->validate([
            'minutes' => 'required|integer|min:5|max:120',
        ]);

        $notification = AdherenceNotification::findOrFail($notificationId);

        if ($notification->user_id !== Auth::user()->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update deadline to snooze time
        $newDeadline = now()->addMinutes($request->minutes);
        $notification->update(['confirmation_deadline' => $newDeadline]);

        return response()->json([
            'message' => "Reminder snoozed for {$request->minutes} minutes",
            'notification' => $notification,
        ]);
    }

    /**
     * Delete a notification from inbox
     */
    public function deleteNotification($notificationId)
    {
        $notification = AdherenceNotification::findOrFail($notificationId);

        if ($notification->user_id !== Auth::user()->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->deleteNotification();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    /**
     * Get pending notifications count
     */
    public function getPendingCount()
    {
        $user = Auth::user();
        $count = AdherenceNotification::where('user_id', $user->user_id)
            ->activeWindow()
            ->count();

        return response()->json(['pending_count' => $count]);
    }

    /**
     * Mark expired notifications as missed (can be run via command or schedule)
     */
    public function markExpiredAsMissed()
    {
        $expiredNotifications = AdherenceNotification::where('status', 'Pending')
            ->where('confirmation_deadline', '<=', now())
            ->get();

        foreach ($expiredNotifications as $notification) {
            $notification->markAsMissed();
        }

        return response()->json([
            'message' => "Marked {$expiredNotifications->count()} expired notifications as missed",
            'count' => $expiredNotifications->count(),
        ]);
    }

    /**
     * Get user's medication adherence rate
     */
    private function getConfirmationRate($userId)
    {
        $confirmed = AdherenceNotification::where('user_id', $userId)
            ->where('status', 'Confirmed')
            ->whereDate('confirmed_at', '>=', now()->subDays(30))
            ->count();

        $total = AdherenceNotification::where('user_id', $userId)
            ->where('status', '!=', 'Deleted')
            ->whereDate('scheduled_at', '>=', now()->subDays(30))
            ->count();

        if ($total === 0) {
            return 0;
        }

        return round(($confirmed / $total) * 100, 2);
    }

    /**
     * Get adherence history for a specific prescription
     */
    public function prescriptionHistory($prescriptionId)
    {
        $prescription = EPrescription::findOrFail($prescriptionId);
        
        $adherenceLogs = $prescription->adherenceLogs()
            ->with('notification')
            ->orderBy('scheduled_datetime', 'desc')
            ->paginate(20);

        return view('adherence.prescription-history', [
            'prescription' => $prescription,
            'adherenceLogs' => $adherenceLogs,
        ]);
    }
}
