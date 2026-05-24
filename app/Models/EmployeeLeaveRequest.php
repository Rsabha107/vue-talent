<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveRequest extends Model
{
    protected $table = 'employee_leave_requests';

    protected $fillable = [
        'archived',
        'employee_id',
        'event_id',
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
        'event_id' => 'integer',
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

    public function event()
    {
        return $this->belongsTo(\App\Models\Ems\Event::class);
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

    /**
     * Scope to filter by event
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $eventId - If null, uses session event
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvent($query, $eventId = null)
    {
        $eventId = $eventId ?? session('selected_event_id');
        
        if ($eventId) {
            return $query->where('event_id', $eventId);
        }
        
        return $query;
    }

    /**
     * Scope to bypass event filtering (for admin views)
     */
    public function scopeAllEvents($query)
    {
        return $query; // Does nothing, just for clarity in code
    }

    // Uncomment below to enable automatic event filtering on ALL queries
    // Use carefully - this will affect every query unless you use ->withoutGlobalScope('event')
    /*
    protected static function booted()
    {
        static::addGlobalScope('event', function ($query) {
            if ($eventId = session('selected_event_id')) {
                $query->where('event_id', $eventId);
            }
        });
    }
    */
}
