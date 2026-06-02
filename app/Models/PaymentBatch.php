<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentBatch extends Model
{
    protected $table = 'payment_batches';

    protected $fillable = [
        'batch_number',
        'batch_name',
        'event_id',
        'period',
        'month_id',
        'year',
        'status',
        'timesheet_count',
        'employee_count',
        'total_amount',
        'finalized_at',
        'finalized_by',
        'processed_at',
        'processed_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'month_id' => 'integer',
        'timesheet_count' => 'integer',
        'employee_count' => 'integer',
        'total_amount' => 'decimal:2',
        'finalized_at' => 'date',
        'finalized_by' => 'integer',
        'processed_at' => 'date',
        'processed_by' => 'integer',
        'created_by' => 'integer',
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentBatchItem::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function processor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function canEdit(): bool
    {
        // Allow editing in draft and finalized status (before processing/payment)
        return $this->status !== 'processed';
    }

    public function canFinalize(): bool
    {
        return $this->status === 'draft' && $this->timesheet_count > 0;
    }

    public function canProcess(): bool
    {
        return $this->status === 'finalized';
    }
}
