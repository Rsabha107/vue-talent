<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'parent_id',
        'name',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active_flag' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Get employees assigned to this department through event assignments
     * Note: department_id is now in employee_events pivot table
     */
    public function employees()
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_events',
            'department_id',
            'employee_id'
        )->wherePivot('is_active', 1);
    }

    /**
     * Get employees for a specific event with this department
     */
    public function employeesForEvent($eventId)
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_events',
            'department_id',
            'employee_id'
        )
        ->wherePivot('event_id', $eventId)
        ->wherePivot('is_active', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('active_flag', true);
    }
}
