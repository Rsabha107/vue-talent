<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentBatchItem extends Model
{
    protected $table = 'payment_batch_items';

    protected $fillable = [
        'payment_batch_id',
        'timesheet_id',
        'employee_id',
        'employee_number',
        'agreement_number',
        'employee_name',
        'role',
        'bank_id',
        'account_number',
        'days_worked',
        'leave_taken',
        'unpaid_leave_taken',
        'total_days_paid',
        'daily_rate',
        'payment_amount',
    ];

    protected $casts = [
        'payment_batch_id' => 'integer',
        'timesheet_id' => 'integer',
        'employee_id' => 'integer',
        'bank_id' => 'integer',
        'days_worked' => 'integer',
        'leave_taken' => 'integer',
        'unpaid_leave_taken' => 'integer',
        'total_days_paid' => 'integer',
        'daily_rate' => 'decimal:2',
        'payment_amount' => 'decimal:2',
    ];

    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class, 'payment_batch_id');
    }

    public function timesheet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class, 'timesheet_id');
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function bank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EmployeeBank::class, 'bank_id');
    }
}
