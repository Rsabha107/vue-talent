<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveRequest extends Model
{
    protected $table = 'employee_leave_requests';

    protected $fillable = [
        'archived',
        'employee_id',
        'user_id',
        'leave_type_id',
        'number_of_days',
        'date_from',
        'date_to',
        'reason',
        'status_id',
        'performer_id',
        'additional_information',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'user_id' => 'integer',
        'leave_type_id' => 'integer',
        'number_of_days' => 'integer',
        'status_id' => 'integer',
        'performer_id' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function status()
    {
        return $this->belongsTo(EmployeeLeaveStatus::class, 'status_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performer_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('archived', 'N');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date_from', [$from, $to])
            ->orWhereBetween('date_to', [$from, $to]);
    }
}
