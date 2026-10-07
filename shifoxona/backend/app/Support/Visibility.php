<?php

namespace App\Support;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Visibility
{
    public static function patients(User $user): Builder
    {
        $query = Patient::query();

        if ($user->role === 'doctor') {
            $query->where('doctor_id', $user->id);
        } elseif ($user->role === 'director') {
            $query->where('hospital_id', $user->hospital_id ?? 0);
        }

        return $query;
    }

    public static function hospitals(User $user): Builder
    {
        $query = Hospital::query();

        if ($user->role !== 'admin') {
            $query->where('id', $user->hospital_id ?? 0);
        }

        return $query;
    }

    public static function doctors(User $user): Builder
    {
        $query = User::where('role', 'doctor');

        if ($user->role === 'director') {
            $query->where('hospital_id', $user->hospital_id ?? 0);
        } elseif ($user->role === 'doctor') {
            $query->where('id', $user->id);
        }

        return $query;
    }
}
