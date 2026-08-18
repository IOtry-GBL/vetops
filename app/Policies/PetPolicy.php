<?php

namespace App\Policies;

use App\Models\Pet;
use App\Models\User;

class PetPolicy
{
    /**
     * Determine if user can view a pet
     */
    public function view(User $user, Pet $pet): bool
    {
        // Owner can view their own pet
        if ($user->id === $pet->user_id) {
            return true;
        }

        // Veterinarian can view if they have a consultation with this pet
        if ($user->role === 'Veterinarian') {
            return $user->consultations()->whereHas('appointment.pet', function ($q) use ($pet) {
                $q->where('pet_id', $pet->pet_id);
            })->exists();
        }

        // Staff can view all pets
        if ($user->role === 'Staff') {
            return true;
        }

        // Admin can view all pets
        if ($user->role === 'Admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can update a pet
     */
    public function update(User $user, Pet $pet): bool
    {
        // Only owner can update their own pet
        return $user->id === $pet->user_id;
    }

    /**
     * Determine if user can delete a pet
     */
    public function delete(User $user, Pet $pet): bool
    {
        // Only owner can delete their own pet
        return $user->id === $pet->user_id;
    }
}
