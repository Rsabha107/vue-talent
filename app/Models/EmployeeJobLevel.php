<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeJobLevel extends Model
{
    use HasFactory;

    protected $table = 'employee_job_level';

    protected $fillable = [
        'title',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active_flag' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_flag', 1);
    }
}
