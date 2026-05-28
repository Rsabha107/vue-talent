<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTimesheet extends Model
{
    protected $table = 'employee_timesheets';

    protected $fillable = [
        'archived',
        'employee_id',
        'user_id',
        'event_id',
        'month_id',
        'year',
        'timesheet_period',
        'days_in_month',
        'status_id',
        'performer_id',
        'additional_information',
        'payroll_approval_id',
        'payroll_additional_information',
        'days_worked',
        'leave_taken',
        'unpaid_leave_taken',
        'total_days_eligible_for_payment',
        'salary',
        'daily_rate',
        'total_payment',
        'bank_id',
        'entries_exists',
        'payroll_reviewed',
        'note_1',
        'note_2',
        'creator_id',
    ];

    protected $casts = [
        'employee_id'   => 'integer',
        'user_id'       => 'integer',
        'event_id'      => 'integer',
        'month_id'      => 'integer',
        'status_id'     => 'integer',
        'performer_id'  => 'integer',
        'days_in_month' => 'integer',
        'days_worked'   => 'integer',
        'leave_taken'   => 'integer',
        'unpaid_leave_taken' => 'integer',
        'total_days_eligible_for_payment' => 'integer',
        'salary'        => 'decimal:2',
        'daily_rate'    => 'decimal:2',
        'total_payment' => 'decimal:2',
        'creator_id'    => 'integer',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Ems\Event::class);
    }

    public function performer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'performer_id');
    }

    public function status(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheetStatus::class, 'status_id');
    }

    public function entries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeTimesheetEntry::class)->orderBy('calendar_day');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('archived', 'N');
    }

    public function scopeForEvent(\Illuminate\Database\Eloquent\Builder $query, $eventId = null): \Illuminate\Database\Eloquent\Builder
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
}
