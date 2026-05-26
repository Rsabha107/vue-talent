<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRelationship extends Model
{
    use HasFactory;

    protected $table = 'employee_relationships';

    protected $fillable = [
        'title',
    ];

    public $timestamps = true;

    // Relationships
    public function emergencyContacts()
    {
        return $this->hasMany(EmployeeEmergencyContact::class, 'relationship_id');
    }
}
