<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAddress extends Model
{
    use HasFactory;

    protected $table = 'employee_addresses';

    protected $fillable = [
        'archived',
        'address_type',
        'primary_address',
        'employee_id',
        'address1',
        'address2',
        'city',
        'state',
        'zipcode',
        'country_id',
        'creator_id',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('archived', 'N');
    }

    // Helper methods
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address1,
            $this->address2,
            $this->city,
            $this->state,
            $this->zipcode,
            $this->country?->name ?? null,
        ]);
        
        return implode(', ', $parts);
    }

    public function isPrimary(): bool
    {
        return $this->primary_address === 'Y';
    }
}
