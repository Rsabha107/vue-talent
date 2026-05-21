<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveStatus extends Model
{
    protected $table = 'employee_leave_status';

    protected $fillable = [
        'title',
        'color',
        'active_flag',
    ];

    protected $casts = [
        'active_flag' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function leaveRequests()
    {
        return $this->hasMany(EmployeeLeaveRequest::class, 'status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active_flag', 1);
    }
}
