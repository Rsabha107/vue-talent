<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $fillable = [
        'title',
        'description',
        'icon',
        'active_flag',
    ];

    protected $casts = [
        'active_flag' => 'integer',
    ];

    // Relationships
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active_flag', 1);
    }
}
