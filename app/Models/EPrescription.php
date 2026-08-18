<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EPrescription extends Model
{
    protected $table = 'e_prescriptions';
    protected $primaryKey = 'prescription_id';
    protected $fillable = ['record_id', 'medication_name', 'dosage', 'frequency', 'duration', 'issued_at', 'status'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    protected $appends = ['current_status'];

    public $timestamps = false;

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'record_id');
    }

    public function adherenceLogs(): HasMany
    {
        return $this->hasMany(AdherenceLog::class, 'prescription_id');
    }

    /**
     * Get the current status as an accessor
     * Returns calculated status: "completed" if end date has passed, otherwise "ongoing"
     */
    public function getCurrentStatusAttribute()
    {
        return $this->calculateStatus();
    }

    /**
     * Get the calculated status based on issued_at and duration
     * Status is "completed" if the prescription end date has passed, otherwise "ongoing"
     */
    public function calculateStatus()
    {
        if (!$this->issued_at || !$this->duration) {
            return 'ongoing';
        }

        $endDate = $this->getEndDate();
        return now()->isAfter($endDate) ? 'completed' : 'ongoing';
    }

    /**
     * Get the end date of the prescription
     */
    public function getEndDate()
    {
        if (!$this->issued_at || !$this->duration) {
            return null;
        }

        // Parse duration (e.g., "7 days", "2 weeks", "1 month")
        $duration = trim($this->duration);
        $parts = explode(' ', $duration);
        
        if (count($parts) < 2) {
            return $this->issued_at;
        }

        $number = (int) $parts[0];
        $unit = strtolower($parts[1]);

        $endDate = $this->issued_at->copy();

        return match($unit) {
            'day', 'days' => $endDate->addDays($number),
            'week', 'weeks' => $endDate->addWeeks($number),
            'month', 'months' => $endDate->addMonths($number),
            'year', 'years' => $endDate->addYears($number),
            default => $endDate,
        };
    }
}

