<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'employee_leave_types';

    protected $fillable = [
        'title',
        'active_flag',
        'accrual_frequency',
        'number_of_leaves',
        'eligible',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active_flag' => 'integer',
        'number_of_leaves' => 'integer',
        'eligible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function eligibilities()
    {
        return $this->hasMany(LeaveEligibility::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(EmployeeLeaveRequest::class, 'leave_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active_flag', 1);
    }

    public function scopeEligible($query)
    {
        return $query->where('eligible', 1);
    }
}
