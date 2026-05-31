<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    use HasFactory;

    protected $table = 'employee_types';

    protected $fillable = [
        'title',
        'active_flag',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'employee_type');
    }
}
