<?php

namespace App\Models;

use App\Services\AdherenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdherenceNotification extends Model
{
    protected $table = 'adherence_notifications';
    protected $primaryKey = 'notification_id';
    protected $fillable = [
        'user_id',
        'adherence_id',
        'medication_name',
        'dosage',
        'scheduled_at',
        'confirmation_deadline',
        'status',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'confirmation_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adherenceLog(): BelongsTo
    {
        return $this->belongsTo(AdherenceLog::class, 'adherence_id');
    }

    public function scopeActiveWindow(Builder $query): Builder
    {
        return $query->where('status', 'Pending')
            ->where('scheduled_at', '<=', now())
            ->where('confirmation_deadline', '>', now());
    }

    // Check if confirmation deadline has passed
    public function isExpired(): bool
    {
        return now()->greaterThan($this->confirmation_deadline);
    }

    public function isAvailableForConfirmation(): bool
    {
        $now = now();

        return $this->status === 'Pending'
            && $now->greaterThanOrEqualTo($this->scheduled_at)
            && $now->lessThanOrEqualTo($this->confirmation_deadline);
    }

    // Check if still pending confirmation
    public function isPending(): bool
    {
        return $this->status === 'Pending' && !$this->isExpired();
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'Pending' && now()->lt($this->scheduled_at);
    }

    // Confirm the adherence
    public function confirm(?string $notes = null): bool
    {
        if (!$this->isAvailableForConfirmation()) {
            return false;
        }

        $confirmedAt = now();

        $this->update([
            'status' => 'Confirmed',
            'confirmed_at' => $confirmedAt,
            'notes' => $notes ?? $this->notes,
        ]);

        $this->adherenceLog()->update([
            'intake_status' => 'Taken',
            'confirmation_time' => $confirmedAt,
        ]);

        return true;
    }

    // Mark as missed
    public function markAsMissed(): void
    {
        $this->update(['status' => 'Missed']);
        $this->adherenceLog()->update(['intake_status' => 'Missed']);
    }

    // Delete notification
    public function deleteNotification(): void
    {
        $this->update(['status' => 'Deleted']);
    }

    public function scheduledAtInClinicTimezone()
    {
        return $this->scheduled_at?->copy()->timezone(AdherenceService::clinicTimezone());
    }

    public function confirmationDeadlineInClinicTimezone()
    {
        return $this->confirmation_deadline?->copy()->timezone(AdherenceService::clinicTimezone());
    }
}
