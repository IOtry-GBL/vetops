<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Pet extends Model
{
    use HasFactory;

    protected $primaryKey = 'pet_id';
    protected $fillable = ['user_id', 'pet_name', 'species', 'breed', 'date_of_birth', 'weight', 'sex'];
    protected $casts = ['date_of_birth' => 'date'];

    public function getIdAttribute(): ?int
    {
        return $this->attributes['pet_id'] ?? null;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['pet_name'] ?? null;
    }

    public function getAgeAttribute(): ?int
    {
        $dateOfBirth = $this->attributes['date_of_birth'] ?? null;
        if (!$dateOfBirth) {
            return null;
        }

        return Carbon::parse($dateOfBirth)->age;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'pet_id');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'pet_id');
    }

    /**
     * Scope: Get pets accessible to a specific user
     */
    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->role === 'Pet Owner') {
            // Owner can only see their own pets
            return $query->where('user_id', $user->user_id);
        } elseif ($user->role === 'Veterinarian') {
            // Vet can see pets they have consultations with
            return $query->whereHas('appointments.consultation', function ($q) use ($user) {
                $q->where('veterinarian_id', $user->id);
            });
        } elseif ($user->role === 'Staff') {
            // Staff can see all pets
            return $query;
        }

        return $query->where('id', null); // No access
    }
}

