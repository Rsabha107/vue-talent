<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryBasis extends Model
{
    use HasFactory;

    protected $table = 'salary_basis';

    protected $fillable = [
        'title',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'salary_basis_id');
    }
}
