<?php

namespace App\Models\Ems;

use App\Models\GlobalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    protected $table = 'events';
    protected $fillable = ['name', 'active_flag', 'event_logo'];

    // protected static function booted(){
    //     appLog(auth()->user()->functional_area_id);
    //     self::addGlobalScope(function(EloquentBuilder $builder){
    //         $builder->when(session()->get('workspace_id'), function ($query, $workspace) {
    //             return $query->where('events.workspace_id', $workspace);
    //         });
    //     });
    // }
    // protected $casts = [
    //     'start_time' => 'datetime: H:i',
    //     'end_time' => 'datetime: H:i',
    //   ];
    protected $appends = ["open"];

    public function getOpenAttribute()
    {
        return true;
    }

    public function activeStatus()
    {
        return $this->belongsTo(GlobalStatus::class, 'active_flag');
    }

    public function venues()
    {
        return $this->belongsToMany(Venue::class, 'venue_event', 'event_id', 'venue_id');
    }

    // Employee relationships
    public function employees()
    {
        return $this->belongsToMany(\App\Models\Employee::class, 'employee_events', 'event_id', 'employee_id')
            ->withPivot([
                'assigned_at',
                'released_at',
                'is_active',
                'agreement_number',
                'entity_id',
                'contract_type_id',
                'department_id',
                'designation_id',
                'directorate_id',
                'functional_area_id',
                'job_level_id',
                'reporting_to_id',
                'employee_type',
                'salary_basis_id',
            ])
            ->withTimestamps();
    }

    public function activeEmployees()
    {
        return $this->employees()->wherePivot('is_active', 1);
    }

    public function leaveRequests()
    {
        return $this->hasMany(\App\Models\EmployeeLeaveRequest::class);
    }
}
