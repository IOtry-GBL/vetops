<?php

namespace App\Services;

use App\Models\AdherenceLog;
use App\Models\AdherenceNotification;
use App\Models\EPrescription;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * AdherenceService
 * 
 * Helper service for managing medication adherence notifications
 * Can be used to integrate with prescription creation and scheduling
 */
class AdherenceService
{
    public const CLINIC_TIMEZONE = 'Asia/Manila';
    public const CONFIRMATION_WINDOW_HOURS = 3;

    private const DAILY_SCHEDULES = [
        1 => ['06:00'],
        2 => ['06:00', '12:00'],
        3 => ['06:00', '12:00', '19:00'],
        4 => ['06:00', '12:00', '18:00', '21:00'],
        5 => ['06:00', '10:00', '14:00', '18:00', '22:00'],
    ];

    public static function clinicTimezone(): string
    {
        return self::CLINIC_TIMEZONE;
    }

    public static function confirmationWindowHours(): int
    {
        return self::CONFIRMATION_WINDOW_HOURS;
    }

    public static function frequencyPerDayFromValue(?string $value): ?int
    {
        $normalized = Str::of((string) $value)
            ->lower()
            ->replace(['-', '/', '_'], ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/\b([1-5])(?:\s*x)?\b/', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        $wordMap = [
            'once' => 1,
            'one' => 1,
            'twice' => 2,
            'two' => 2,
            'bid' => 2,
            'thrice' => 3,
            'three' => 3,
            'tid' => 3,
            'four' => 4,
            'qid' => 4,
            'five' => 5,
        ];

        foreach ($wordMap as $needle => $frequency) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $normalized) === 1) {
                return $frequency;
            }
        }

        return null;
    }

    public static function durationDaysFromValue(?string $value): ?int
    {
        $normalized = Str::of((string) $value)
            ->lower()
            ->replace(['-', '/', '_'], ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/\b(\d{1,3})\b/', $normalized, $matches) === 1) {
            $days = (int) $matches[1];

            return $days >= 1 && $days <= 365 ? $days : null;
        }

        $wordMap = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'seven' => 7,
            'ten' => 10,
            'fourteen' => 14,
            'thirty' => 30,
        ];

        foreach ($wordMap as $needle => $days) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $normalized) === 1) {
                return $days;
            }
        }

        return null;
    }

    public static function normalizeFrequency(?string $value): ?string
    {
        return match (self::frequencyPerDayFromValue($value)) {
            1 => 'Once daily',
            2 => 'Twice daily',
            3 => '3 times daily',
            4 => '4 times daily',
            5 => '5 times daily',
            default => null,
        };
    }

    public static function normalizeDuration(?string $value): ?string
    {
        $days = self::durationDaysFromValue($value);

        if ($days === null) {
            return null;
        }

        return $days === 1 ? '1 day' : "{$days} days";
    }

    public static function buildDoseSchedule(?string $frequencyValue, ?string $durationValue, ?Carbon $issuedAt = null): array
    {
        $frequency = self::frequencyPerDayFromValue($frequencyValue);
        $durationDays = self::durationDaysFromValue($durationValue);

        if ($frequency === null || $durationDays === null) {
            return [];
        }

        $issuedAtLocal = ($issuedAt ? $issuedAt->copy() : now())->timezone(self::clinicTimezone());
        $scheduleTemplate = self::DAILY_SCHEDULES[$frequency] ?? self::DAILY_SCHEDULES[1];
        $totalDoses = $frequency * $durationDays;
        $scheduledTimes = [];
        $cursorDate = $issuedAtLocal->copy()->startOfDay();

        while (count($scheduledTimes) < $totalDoses) {
            foreach ($scheduleTemplate as $time) {
                $scheduledLocal = $cursorDate->copy()->setTimeFromTimeString($time);

                if ($scheduledLocal->lt($issuedAtLocal)) {
                    continue;
                }

                $scheduledTimes[] = $scheduledLocal->copy()->utc();

                if (count($scheduledTimes) === $totalDoses) {
                    break;
                }
            }

            $cursorDate->addDay();
        }

        return $scheduledTimes;
    }

    public static function createDoseScheduleForPrescription(EPrescription $prescription): array
    {
        return self::createRemindersForPrescription(
            $prescription,
            self::buildDoseSchedule($prescription->frequency, $prescription->duration, $prescription->issued_at)
        );
    }

    public static function buildConfirmationDeadline(Carbon $scheduledTime): Carbon
    {
        return $scheduledTime->copy()->addHours(self::confirmationWindowHours());
    }

    /**
     * Create adherence reminder for a medication dose
     */
    public static function createReminderForDose(AdherenceLog $adherenceLog): ?AdherenceNotification
    {
        $adherenceLog->loadMissing('prescription.medicalRecord.pet.owner', 'notification');

        $prescription = $adherenceLog->prescription;
        if (!$prescription || !$prescription->medicalRecord) {
            return null;
        }

        $user = $prescription->medicalRecord->pet->owner;
        if (!$user) {
            return null;
        }

        $scheduledTime = $adherenceLog->scheduled_datetime instanceof Carbon
            ? $adherenceLog->scheduled_datetime->copy()
            : Carbon::parse($adherenceLog->scheduled_datetime);
        $deadlineTime = self::buildConfirmationDeadline($scheduledTime);

        // Update adherence log
        $adherenceLog->update([
            'confirmation_deadline' => $deadlineTime,
            'is_notified' => true,
        ]);

        return AdherenceNotification::updateOrCreate(
            ['adherence_id' => $adherenceLog->adherence_id],
            [
                'user_id' => $user->user_id,
                'medication_name' => $prescription->medication_name,
                'dosage' => $prescription->dosage,
                'scheduled_at' => $scheduledTime,
                'confirmation_deadline' => $deadlineTime,
                'status' => $adherenceLog->intake_status === 'Pending' ? 'Pending' : 'Confirmed',
            ]
        );
    }

    /**
     * Create reminders for a prescription dose schedule
     */
    public static function createRemindersForPrescription(EPrescription $prescription, array $scheduleTimes): array
    {
        $notifications = [];

        foreach ($scheduleTimes as $scheduledTime) {
            $scheduledTime = $scheduledTime instanceof Carbon
                ? $scheduledTime->copy()
                : Carbon::parse($scheduledTime);

            $adherenceLog = AdherenceLog::firstOrCreate(
                [
                    'prescription_id' => $prescription->prescription_id,
                    'scheduled_datetime' => $scheduledTime,
                ],
                [
                    'confirmation_deadline' => self::buildConfirmationDeadline($scheduledTime),
                    'intake_status' => 'Pending',
                ]
            );

            $notification = self::createReminderForDose($adherenceLog);
            if ($notification) {
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    public static function ensureNextPendingDoseNotificationForUser(int $userId): ?AdherenceNotification
    {
        $nextDose = AdherenceLog::with('prescription.medicalRecord.pet.owner', 'notification')
            ->where('intake_status', 'Pending')
            ->whereHas('prescription.medicalRecord.pet', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('scheduled_datetime')
            ->first();

        return $nextDose ? self::createReminderForDose($nextDose) : null;
    }

    public static function markExpiredPendingDosesForUser(int $userId): int
    {
        $expiredLogs = AdherenceLog::with('prescription.medicalRecord.pet.owner', 'notification')
            ->where('intake_status', 'Pending')
            ->whereHas('prescription.medicalRecord.pet', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get()
            ->filter(fn (AdherenceLog $log) => $log->isExpiredForConfirmation());

        $count = 0;

        foreach ($expiredLogs as $log) {
            self::markDoseAsMissed($log);
            $count++;
        }

        return $count;
    }

    public static function markExpiredPendingDosesForPrescription(EPrescription $prescription): int
    {
        $expiredLogs = $prescription->adherenceLogs()
            ->with('prescription.medicalRecord.pet.owner', 'notification')
            ->where('intake_status', 'Pending')
            ->get()
            ->filter(fn (AdherenceLog $log) => $log->isExpiredForConfirmation());

        $count = 0;

        foreach ($expiredLogs as $log) {
            self::markDoseAsMissed($log);
            $count++;
        }

        return $count;
    }

    public static function markDoseAsMissed(AdherenceLog $adherenceLog): void
    {
        $adherenceLog->loadMissing('prescription.medicalRecord.pet.owner', 'notification');

        $deadline = $adherenceLog->resolvedConfirmationDeadline();

        $adherenceLog->update([
            'confirmation_deadline' => $deadline,
            'intake_status' => 'Missed',
            'is_notified' => true,
        ]);

        $notification = $adherenceLog->notification;

        if ($notification) {
            $notification->update([
                'scheduled_at' => $adherenceLog->scheduled_datetime,
                'confirmation_deadline' => $deadline,
                'status' => 'Missed',
            ]);

            return;
        }

        $prescription = $adherenceLog->prescription;
        $owner = $prescription?->medicalRecord?->pet?->owner;

        if (!$prescription || !$owner) {
            return;
        }

        AdherenceNotification::create([
            'user_id' => $owner->user_id,
            'adherence_id' => $adherenceLog->adherence_id,
            'medication_name' => $prescription->medication_name,
            'dosage' => $prescription->dosage,
            'scheduled_at' => $adherenceLog->scheduled_datetime,
            'confirmation_deadline' => $deadline,
            'status' => 'Missed',
        ]);
    }

    /**
     * Get user's adherence statistics
     */
    public static function getUserAdherenceStats($userId, $days = 30)
    {
        $startDate = now()->subDays($days);

        $total = AdherenceNotification::where('user_id', $userId)
            ->where('status', '!=', 'Deleted')
            ->where('scheduled_at', '>=', $startDate)
            ->count();

        $confirmed = AdherenceNotification::where('user_id', $userId)
            ->where('status', 'Confirmed')
            ->where('scheduled_at', '>=', $startDate)
            ->count();

        $missed = AdherenceNotification::where('user_id', $userId)
            ->where('status', 'Missed')
            ->where('scheduled_at', '>=', $startDate)
            ->count();

        $pending = AdherenceNotification::where('user_id', $userId)
            ->where('status', 'Pending')
            ->where('scheduled_at', '<=', now())
            ->where('confirmation_deadline', '>', now())
            ->count();

        $confirmationRate = $total > 0 ? round(($confirmed / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'missed' => $missed,
            'pending' => $pending,
            'confirmation_rate' => $confirmationRate,
            'period_days' => $days,
        ];
    }

    /**
     * Get pet's adherence statistics
     */
    public static function getPetAdherenceStats($petId, $days = 30)
    {
        $startDate = now()->subDays($days);

        $prescriptions = EPrescription::whereHas('medicalRecord', function ($query) use ($petId) {
            $query->where('pet_id', $petId);
        })->pluck('prescription_id');

        $total = AdherenceLog::whereIn('prescription_id', $prescriptions)
            ->where('created_at', '>=', $startDate)
            ->count();

        $taken = AdherenceLog::whereIn('prescription_id', $prescriptions)
            ->where('intake_status', 'Taken')
            ->where('created_at', '>=', $startDate)
            ->count();

        $missed = AdherenceLog::whereIn('prescription_id', $prescriptions)
            ->where('intake_status', 'Missed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $adherenceRate = $total > 0 ? round(($taken / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'taken' => $taken,
            'missed' => $missed,
            'adherence_rate' => $adherenceRate,
            'period_days' => $days,
        ];
    }

    /**
     * Bulk mark expired notifications as missed
     */
    public static function markExpiredAsMissed(): int
    {
        $expired = AdherenceLog::with('prescription.medicalRecord.pet.owner', 'notification')
            ->where('intake_status', 'Pending')
            ->get()
            ->filter(fn (AdherenceLog $log) => $log->isExpiredForConfirmation());

        $count = 0;
        foreach ($expired as $log) {
            self::markDoseAsMissed($log);
            $count++;
        }

        return $count;
    }

    /**
     * Clean up old deleted notifications
     */
    public static function cleanupDeletedNotifications($daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return AdherenceNotification::where('status', 'Deleted')
            ->where('updated_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Get adherence report for a prescription
     */
    public static function getPrescriptionAdherenceReport($prescriptionId)
    {
        $prescription = EPrescription::findOrFail($prescriptionId);
        $logs = $prescription->adherenceLogs()->get();

        $stats = [
            'medication_name' => $prescription->medication_name,
            'dosage' => $prescription->dosage,
            'frequency' => $prescription->frequency,
            'duration' => $prescription->duration,
            'issued_at' => $prescription->issued_at,
            'total_doses' => $logs->count(),
            'taken' => $logs->where('intake_status', 'Taken')->count(),
            'missed' => $logs->where('intake_status', 'Missed')->count(),
            'pending' => $logs->where('intake_status', 'Pending')->count(),
            'delayed' => $logs->where('intake_status', 'Delayed')->count(),
        ];

        $stats['adherence_rate'] = $stats['total_doses'] > 0
            ? round(($stats['taken'] / $stats['total_doses']) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Send custom note/reminder to user
     */
    public static function addNoteToNotification($notificationId, $note): bool
    {
        $notification = AdherenceNotification::find($notificationId);
        if ($notification) {
            $notification->update(['notes' => $note]);
            return true;
        }
        return false;
    }

    /**
     * Get upcoming medications for user
     */
    public static function getUpcomingMedications($userId, $hoursAhead = 24)
    {
        $now = now();
        $future = now()->addHours($hoursAhead);

        return AdherenceNotification::where('user_id', $userId)
            ->where('status', 'Pending')
            ->whereBetween('scheduled_at', [$now, $future])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }
}
