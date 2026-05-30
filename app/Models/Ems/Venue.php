<?php

namespace App\Models\Ems;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;
    protected $table = 'venues';
    protected $fillable = ['title', 'short_name', 'active_flag', 'created_by', 'updated_by'];
}
