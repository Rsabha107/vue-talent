<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory;

    protected $table = 'relationships';

    protected $fillable = [
        'title',
        'created_by',
        'updated_by',
    ];

    // Note: This is a lookup table for relationship types (e.g., spouse, child, parent)
    // If you have an employee_relationships pivot table, add that relationship here
}
