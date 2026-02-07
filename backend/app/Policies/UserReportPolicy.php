<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserReportPolicy
{
    /**
     * Determine whether the user can view any models (only their own reports).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'user';
    }

    /**
     * Determine whether the user can view the model (only their own report).
     */
    public function view(User $user, Complaint $complaint): bool
    {
        return $user->role === 'user' && $user->id === $complaint->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'user';
    }

    /**
     * Determine whether the user can update the model (not allowed for users).
     */
    public function update(User $user, Complaint $complaint): bool
    {
        return false; // Users cannot update reports
    }

    /**
     * Determine whether the user can delete the model (not allowed for users).
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        return false; // Users cannot delete reports
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Complaint $complaint): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Complaint $complaint): bool
    {
        return false;
    }
}
