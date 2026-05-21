<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEntity extends Model
{
    use HasFactory;

    protected $table = 'employee_entity';

    protected $fillable = [
        'title',
        'created_by',
        'updated_by',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'entity_id');
    }
}
