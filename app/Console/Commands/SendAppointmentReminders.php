<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send appointment reminders at scheduled times (1 day before, 1 hour before, exact time)';

    public function handle()
    {
        $now = Carbon::now();

        // 1 DAY BEFORE REMINDERS
        $oneDayBefore = $now->copy()->addDay()->startOfDay();
        $oneDayBeforeEnd = $oneDayBefore->copy()->endOfDay();

        $upcomingAppointments = Appointment::whereBetween('appointment_date', [
            $oneDayBefore->toDateString(),
            $oneDayBeforeEnd->toDateString(),
        ])
        ->where('status', 'Approved')
        ->get();

        foreach ($upcomingAppointments as $appointment) {
            // Check if reminder already sent
            $existing = Notification::where('user_id', $appointment->pet->owner->user_id)
                ->where('reminder_type', 'appointment_reminder_1day')
                ->where('related_reference', $appointment->appointment_id)
                ->where('sent_status', 'sent')
                ->exists();

            if (!$existing) {
                Notification::create([
                    'user_id' => $appointment->pet->owner->user_id,
                    'reminder_type' => 'appointment_reminder_1day',
                    'reminder_channel' => 'in-app',
                    'scheduled_at' => now(),
                    'sent_status' => 'sent',
                    'related_reference' => $appointment->appointment_id,
                ]);
            }
        }

        // 1 HOUR BEFORE REMINDERS
        $oneHourBefore = $now->copy()->addHour();
        $appointmentsOneHourBefore = Appointment::where('appointment_date', $now->toDateString())
            ->where('status', 'Approved')
            ->get()
            ->filter(function ($appt) use ($oneHourBefore) {
                $appointmentTime = Carbon::createFromFormat('Y-m-d H:i', 
                    $appt->appointment_date . ' ' . $appt->appointment_time
                );
                return $appointmentTime->diffInMinutes($oneHourBefore) <= 5 && 
                       $appointmentTime->diffInMinutes($oneHourBefore) >= -5;
            });

        foreach ($appointmentsOneHourBefore as $appointment) {
            $existing = Notification::where('user_id', $appointment->pet->owner->user_id)
                ->where('reminder_type', 'appointment_reminder_1hour')
                ->where('related_reference', $appointment->appointment_id)
                ->where('sent_status', 'sent')
                ->exists();

            if (!$existing) {
                Notification::create([
                    'user_id' => $appointment->pet->owner->user_id,
                    'reminder_type' => 'appointment_reminder_1hour',
                    'reminder_channel' => 'in-app',
                    'scheduled_at' => now(),
                    'sent_status' => 'sent',
                    'related_reference' => $appointment->appointment_id,
                ]);
            }
        }

        // EXACT TIME REMINDERS
        $appointmentsNow = Appointment::where('appointment_date', $now->toDateString())
            ->where('status', 'Approved')
            ->get()
            ->filter(function ($appt) use ($now) {
                $appointmentTime = Carbon::createFromFormat('Y-m-d H:i', 
                    $appt->appointment_date . ' ' . $appt->appointment_time
                );
                return $appointmentTime->diffInMinutes($now) <= 2 && 
                       $appointmentTime->diffInMinutes($now) >= -2;
            });

        foreach ($appointmentsNow as $appointment) {
            $existing = Notification::where('user_id', $appointment->pet->owner->user_id)
                ->where('reminder_type', 'appointment_time_reminder')
                ->where('related_reference', $appointment->appointment_id)
                ->where('sent_status', 'sent')
                ->exists();

            if (!$existing) {
                Notification::create([
                    'user_id' => $appointment->pet->owner->user_id,
                    'reminder_type' => 'appointment_time_reminder',
                    'reminder_channel' => 'in-app',
                    'scheduled_at' => now(),
                    'sent_status' => 'sent',
                    'related_reference' => $appointment->appointment_id,
                ]);
            }
        }

        $this->info('Appointment reminders sent successfully.');
    }
}
