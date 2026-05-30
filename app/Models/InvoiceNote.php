<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceNote extends Model
{
    use HasFactory;

    protected $table = 'invoice_notes';

    protected $fillable = [
        'title',
        'note',
        'created_by',
        'updated_by',
    ];
}
