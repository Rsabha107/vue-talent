<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'iso',
        'name',
        'country_name',
        'iso3',
        'numcode',
        'phonecode',
    ];

    public $timestamps = true;
}
