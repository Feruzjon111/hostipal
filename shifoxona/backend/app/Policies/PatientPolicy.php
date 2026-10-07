<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function view(User $user, Patient $patient): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'director' && $user->hospital_id === $patient->hospital_id)
            || ($user->role === 'doctor' && $user->id === $patient->doctor_id);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin' || ($user->role === 'doctor' && $user->hospital_id !== null);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->role === 'admin' || ($user->role === 'doctor' && $user->id === $patient->doctor_id);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $this->update($user, $patient);
    }
}
