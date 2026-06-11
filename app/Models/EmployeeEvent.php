<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeEvent extends Pivot
{
    protected $table = 'employee_events';

    protected $fillable = [
        'employee_id',
        'event_id',
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
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'released_at' => 'date',
        'is_active' => 'boolean',
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class);
    }

    public function functionalArea()
    {
        return $this->belongsTo(FunctionalArea::class);
    }

    public function entity()
    {
        return $this->belongsTo(EmployeeEntity::class);
    }

    public function contractType()
    {
        return $this->belongsTo(EmployeeContractType::class);
    }

    public function reportingTo()
    {
        return $this->belongsTo(Employee::class, 'reporting_to_id');
    }

    public function salaryBasis()
    {
        return $this->belongsTo(SalaryBasis::class);
    }

    public function employeeType()
    {
        return $this->belongsTo(EmployeeType::class, 'employee_type');
    }

    public function jobLevel()
    {
        return $this->belongsTo(EmployeeJobLevel::class, 'job_level_id');
    }
}
