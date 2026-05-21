<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveEligibility extends Model
{
    protected $table = 'leave_eligibilities';

    protected $fillable = [
        'leave_type_id',
        'gender_id',
        'contract_type_id',
        'department_id',
        'designation_id',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
