<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTeamTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'expected_team_size',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expected_team_size' => 'integer',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(EventTeamTemplateRole::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
