<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'contact_no',
        'role',
        'status',
        'is_super_admin',
        'impersonating_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'status' => 'string',
            'is_super_admin' => 'boolean',
            'impersonating_role' => 'string',
        ];
    }

    public function getIdAttribute(): ?int
    {
        return $this->attributes['user_id'] ?? null;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['full_name'] ?? null;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['contact_no'] ?? null;
    }

    /**
     * Get user's pets (only for pet owners)
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'user_id');
    }

    /**
     * Get consultations conducted by veterinarian
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'veterinarian_id');
    }

    /**
     * Get prescriptions written by veterinarian
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'vet_id');
    }

    /**
     * Get notifications for user
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * Get consultation messages authored by the user.
     */
    public function consultationMessages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class, 'sender_id', 'user_id');
    }

    /**
     * Translate short role name to database enum value
     *
     * @param string $shortRole
     * @return string
     */
    public function translateRole($shortRole)
    {
        $roleMap = [
            'owner' => 'Pet Owner',
            'vet' => 'Veterinarian',
            'staff' => 'Staff',
            'admin' => 'Admin',
            'pet owner' => 'Pet Owner',
            'veterinarian' => 'Veterinarian',
        ];

        return $roleMap[strtolower($shortRole)] ?? $shortRole;
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        // Super admin can access any role
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Translate short role name to database enum value
        $requiredRole = $this->translateRole($role);
        return $this->role === $requiredRole;
    }

    /**
     * Check if user is a pet owner
     *
     * @return bool
     */
    public function isPetOwner()
    {
        return $this->role === 'Pet Owner';
    }

    /**
     * Check if user is a veterinarian
     *
     * @return bool
     */
    public function isVeterinarian()
    {
        return $this->role === 'Veterinarian';
    }

    /**
     * Check if user is clinic staff
     *
     * @return bool
     */
    public function isStaff()
    {
        return $this->role === 'Staff';
    }

    /**
     * Check if user is an admin (admin or super admin)
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user is a super admin
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->is_super_admin === true || $this->is_super_admin === 1;
    }

    /**
     * Determine whether the user can access a named permission.
     */
    public function canAccessPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $rolePermissions = config('super-admin.roles.' . $this->role . '.permissions', []);

        return in_array('all', $rolePermissions, true) || in_array($permission, $rolePermissions, true);
    }

    /**
     * Get current role (either actual or impersonating)
     *
     * @return string
     */
    public function getCurrentRole()
    {
        if ($this->isSuperAdmin() && $this->impersonating_role) {
            return $this->impersonating_role;
        }
        return $this->role;
    }
}
