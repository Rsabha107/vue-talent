<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeContractType extends Model
{
    use HasFactory;

    protected $table = 'employee_contract_types';

    protected $fillable = [
        'title',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'contract_type_id');
    }
}
