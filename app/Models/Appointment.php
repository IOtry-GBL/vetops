<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Appointment extends Model
{
    use HasFactory;

    public const ACTIVE_STATUSES = ['Pending', 'Approved', 'Rescheduled'];

    protected $primaryKey = 'appointment_id';
    protected $fillable = ['pet_id', 'appointment_date', 'appointment_time', 'consultation_mode', 'reason_for_visit', 'status', 'proof_of_payment'];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i:s',
    ];

    public function getIdAttribute(): ?int
    {
        return $this->attributes['appointment_id'] ?? null;
    }

    public function getReasonAttribute(): ?string
    {
        return $this->attributes['reason_for_visit'] ?? null;
    }

    public function getAppointmentDateAttribute($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $date = Carbon::parse($value);
        $time = $this->attributes['appointment_time'] ?? null;

        if ($time) {
            $parts = explode(':', $time);
            $hour = (int) ($parts[0] ?? 0);
            $minute = (int) ($parts[1] ?? 0);
            $second = (int) ($parts[2] ?? 0);
            $date->setTime($hour, $minute, $second);
        }

        return $date;
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class, 'appointment_id', 'appointment_id');
    }

    public function scopeActiveEntries(Builder $query): Builder
    {
        $now = now('Asia/Manila');

        return $query
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where(function (Builder $query) use ($now) {
                $query
                    ->whereDate('appointment_date', '>', $now->toDateString())
                    ->orWhere(function (Builder $query) use ($now) {
                        $query
                            ->whereDate('appointment_date', $now->toDateString())
                            ->whereTime('appointment_time', '>=', $now->format('H:i:s'));
                    });
            });
    }

    public static function petHasActiveEntry(int $petId, ?int $ignoreAppointmentId = null): bool
    {
        return static::query()
            ->activeEntries()
            ->where('pet_id', $petId)
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->where('appointment_id', '!=', $ignoreAppointmentId))
            ->exists();
    }
}
