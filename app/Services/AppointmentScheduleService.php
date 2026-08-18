<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class AppointmentScheduleService
{
    public const WORKING_HOURS = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    public static function bookedAppointments(?int $ignoreAppointmentId = null): array
    {
        return Appointment::query()
            ->activeEntries()
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->where('appointment_id', '!=', $ignoreAppointmentId))
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->appointment_date->format('Y-m-d'))
            ->map(fn ($appointments) => $appointments
                ->map(fn (Appointment $appointment) => $appointment->appointment_time->format('H:i'))
                ->values()
                ->all()
            )
            ->all();
    }

    public static function normalizeDateTimeInput(Request $request): void
    {
        $appointmentDateTime = $request->input('appointment_date');

        if ($appointmentDateTime && str_contains($appointmentDateTime, 'T') && !$request->filled('appointment_time')) {
            [$date, $time] = explode('T', $appointmentDateTime, 2);
            $request->merge([
                'appointment_date' => $date,
                'appointment_time' => substr($time, 0, 5),
            ]);
        }
    }

    public static function validateSlot(Validator $validator, Request $request, ?int $ignoreAppointmentId = null): void
    {
        $date = $request->input('appointment_date');
        $time = $request->input('appointment_time');

        if (!$date || !$time) {
            return;
        }

        if (!in_array($time, self::WORKING_HOURS, true)) {
            $validator->errors()->add('appointment_time', 'Choose one of the available clinic time slots.');
            return;
        }

        try {
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", 'Asia/Manila');
        } catch (\Throwable) {
            return;
        }

        if ($scheduledAt->lessThan(now('Asia/Manila'))) {
            $validator->errors()->add('appointment_date', 'Choose a future appointment date and time.');
            return;
        }

        $slotTaken = Appointment::query()
            ->activeEntries()
            ->whereDate('appointment_date', $date)
            ->whereTime('appointment_time', $time)
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->where('appointment_id', '!=', $ignoreAppointmentId))
            ->exists();

        if ($slotTaken) {
            $validator->errors()->add('appointment_time', 'That appointment slot is already taken.');
        }
    }
}
