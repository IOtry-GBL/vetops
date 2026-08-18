<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentNotification extends Model
{
    protected $table = 'appointment_notifications';
    protected $fillable = ['appointment_id', 'notification_status'];

    public static function recordStatus(int $appointmentId, string $status): self
    {
        $normalizedStatus = ucfirst(strtolower(trim($status)));

        return static::updateOrCreate(
            [
                'appointment_id' => $appointmentId,
                'notification_status' => $normalizedStatus,
            ],
            [
                'appointment_id' => $appointmentId,
                'notification_status' => $normalizedStatus,
            ]
        );
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function delete()
    {
        $this->update(['notification_status' => 'deleted']);
    }
}
