<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salutation extends Model
{
    use HasFactory;

    protected $table = 'salutations';

    protected $fillable = [
        'title',
        'created_by',
        'updated_by',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
