<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FunctionalArea extends Model
{
    use HasFactory;

    protected $table = 'functional_areas';

    protected $fillable = [
        'fa_code',
        'title',
        'focal_point_name',
        'focal_point_email',
        'fax_id',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active_flag' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active_flag', true);
    }
}
