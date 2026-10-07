<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    protected $fillable = ['name', 'region_id', 'district_id', 'tin'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function doctors(): HasMany
    {
        return $this->users()->where('role', 'doctor');
    }

    public function directors(): HasMany
    {
        return $this->users()->where('role', 'director');
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
