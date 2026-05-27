<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'employee_emergency_contacts';

    protected $fillable = [
        'archived',
        'employee_id',
        'first_name',
        'last_name',
        'relationship_id',
        'contact_number',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function relationship()
    {
        return $this->belongsTo(EmployeeRelationship::class, 'relationship_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('archived', 'N');
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
