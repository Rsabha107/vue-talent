<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $table = 'employee_salary';

    protected $fillable = [
        'archived',
        'employee_id',
        'payroll_cycle_id',
        'net_salary',
        'effective_start_date',
        'effective_end_date',
        'active_flag',
        'creator_id',
    ];

    protected $casts = [
        'net_salary' => 'decimal:3',
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'active_flag' => 'integer',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active_flag', 1)
                    ->where('archived', 'N');
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where(function($q) use ($now) {
            $q->where('effective_start_date', '<=', $now)
              ->where(function($q2) use ($now) {
                  $q2->whereNull('effective_end_date')
                     ->orWhere('effective_end_date', '>=', $now);
              });
        });
    }

    // Helper methods
    public function isActive(): bool
    {
        $now = now();
        return $this->effective_start_date <= $now 
            && (!$this->effective_end_date || $this->effective_end_date >= $now)
            && $this->active_flag === 1;
    }
}
