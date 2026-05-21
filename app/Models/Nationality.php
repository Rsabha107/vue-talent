<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    use HasFactory;

    protected $table = 'nationalities';

    protected $fillable = [
        'num_code',
        'alpha_2_code',
        'alpha_3_code',
        'en_short_name',
        'nationality',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'nationality_id');
    }
}
