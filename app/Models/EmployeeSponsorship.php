<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSponsorship extends Model
{
    use HasFactory;

    protected $table = 'employee_sponsorship';

    protected $fillable = [
        'title',
        'active_flag',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active_flag' => 'integer',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'sponsorship_id');
    }
}
