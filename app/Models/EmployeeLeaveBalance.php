<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBalance extends Model
{
    protected $table = 'employee_leave_balances';

    protected $fillable = [
        'employee_id',
        'event_id',
        'leave_type_id',
        'allocated_days',
        'used_days',
        'pending_days',
        'available_days',
        'year',
        'period_start',
        'period_end',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'event_id' => 'integer',
        'leave_type_id' => 'integer',
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'available_days' => 'decimal:2',
        'year' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'active_flag' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active_flag', 1);
    }

    public function scopeForYear($query, $year = null)
    {
        $year = $year ?? date('Y');
        return $query->where('year', $year);
    }

    public function scopeForEvent($query, $eventId = null)
    {
        $eventId = $eventId ?? session('selected_event_id');
        
        if ($eventId === null) {
            return $query; // No filter - show all
        }
        
        if (is_array($eventId)) {
            return $query->whereIn('event_id', $eventId);
        }
        
        return $query->where('event_id', $eventId);
    }

    /**
     * Recalculate available days based on allocated, used, and pending
     */
    public function recalculateAvailable()
    {
        $this->available_days = $this->allocated_days - $this->used_days - $this->pending_days;
        $this->save();
        
        return $this;
    }
}
