<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTimesheetEntry extends Model
{
    protected $table = 'employee_timesheet_entries';

    protected $fillable = [
        'employee_timesheet_id',
        'employee_id',
        'user_id',
        'calendar_day',
        'day_action',
    ];

    protected $casts = [
        'employee_timesheet_id' => 'integer',
        'employee_id'           => 'integer',
        'user_id'               => 'integer',
        'calendar_day'          => 'integer',
    ];

    public function timesheet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class, 'employee_timesheet_id');
    }
}
