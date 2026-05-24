<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTeamTemplateRole extends Model
{
    protected $fillable = [
        'template_id',
        'role_name',
        'suggested_count',
        'is_required',
        'display_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'suggested_count' => 'integer',
        'display_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EventTeamTemplate::class, 'template_id');
    }
}
