<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees_all';

    protected $fillable = [
        'user_id',
        'archived',
        'employee_number',
        'agreement_number',
        'salary_basis_id',
        'national_identifier_number',
        'salutation_id',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'gender_id',
        'marital_status_id',
        'employee_type',
        'entity_id',
        'contract_type_id',
        'contract_start_date',
        'contract_end_date',
        'sponsorship_id',
        'sponsorship_name',
        'date_of_birth',
        'date_of_hire',
        'join_date',
        'town_of_birth',
        'country_of_birth',
        'personal_email_address',
        'work_email_address',
        'phone_number',
        'alt_phone_number',
        'phone_area_code',
        'alt_area_code',
        'nationality_id',
        'language_id',
        'reporting_to_id',
        'department_id',
        'designation_id',
        'directorate_id',
        'functional_area_id',
        'job_level_id',
        'civil_id_expiry',
        'passport_number',
        'passport_expiry',
        'manager_flag',
        'administrator_flag',
        'profile_photo',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_hire' => 'date',
        'join_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'civil_id_expiry' => 'date',
        'passport_expiry' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(\App\Models\Designation::class);
    }

    public function salutation()
    {
        return $this->belongsTo(\App\Models\Salutation::class);
    }

    public function directorate()
    {
        return $this->belongsTo(\App\Models\Directorate::class);
    }

    public function functionalArea()
    {
        return $this->belongsTo(\App\Models\FunctionalArea::class);
    }

    public function reportingTo()
    {
        return $this->belongsTo(Employee::class, 'reporting_to_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'reporting_to_id');
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class, 'marital_status_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function entity()
    {
        return $this->belongsTo(EmployeeEntity::class, 'entity_id');
    }

    public function contractType()
    {
        return $this->belongsTo(EmployeeContractType::class, 'contract_type_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(EmployeeLeaveRequest::class, 'employee_id');
    }

    // Event relationships
    public function events()
    {
        return $this->belongsToMany(\App\Models\Ems\Event::class, 'employee_events', 'employee_id', 'event_id')
            ->withPivot(['assigned_at', 'released_at', 'event_role', 'event_department_id', 'is_active'])
            ->withTimestamps();
    }

    // Personal data relationships
    public function addresses()
    {
        return $this->hasMany(EmployeeAddress::class, 'employee_id');
    }

    public function banks()
    {
        return $this->hasMany(EmployeeBank::class, 'employee_id');
    }

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id');
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmployeeEmergencyContact::class, 'employee_id');
    }

    public function activeEvents()
    {
        return $this->events()->wherePivot('is_active', 1);
    }

    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    // Accessors
    public function getInitialsAttribute()
    {
        $first = substr($this->first_name ?? '', 0, 1);
        $last = substr($this->last_name ?? '', 0, 1);
        return strtoupper($first . $last);
    }

    public function getAvatarColorAttribute()
    {
        // Generate a consistent color based on employee ID
        return $this->id % 7;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('archived', 'N');
    }

    public function scopeArchived($query)
    {
        return $query->where('archived', 'Y');
    }

    /**
     * Scope to filter employees by event assignment
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $eventId - If null, uses session event
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvent($query, $eventId = null)
    {
        $eventId = $eventId ?? session('selected_event_id');
        
        if ($eventId) {
            return $query->whereHas('events', function ($q) use ($eventId) {
                $q->where('events.id', $eventId)
                  ->where('employee_events.is_active', 1);
            });
        }
        
        return $query;
    }
}
