<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBank extends Model
{
    use HasFactory;

    protected $table = 'employee_banks';

    protected $fillable = [
        'archived',
        'employee_id',
        'user_id',
        'bank_branch_name',
        'bank_account_name',
        'iban',
        'swift_code',
        'effective_start_date',
        'effective_end_date',
    ];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('archived', 'N');
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
            && (!$this->effective_end_date || $this->effective_end_date >= $now);
    }
}
