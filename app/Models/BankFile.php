<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankFile extends Model
{
    protected $table = 'bank_files';

    protected $fillable = [
        'payment_batch_id',
        'file_name',
        'file_path',
        'file_format',
        'record_count',
        'total_amount',
        'generation_notes',
        'generated_by',
    ];

    protected $casts = [
        'payment_batch_id' => 'integer',
        'record_count' => 'integer',
        'total_amount' => 'decimal:2',
        'generated_by' => 'integer',
    ];

    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class, 'payment_batch_id');
    }

    public function generator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Helper methods
    public function getFileSize(): int
    {
        if (\Storage::disk('local')->exists($this->file_path)) {
            return \Storage::disk('local')->size($this->file_path);
        }
        return 0;
    }

    public function exists(): bool
    {
        return \Storage::disk('local')->exists($this->file_path);
    }
}
