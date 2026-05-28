<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTimesheetStatus extends Model
{
    // Status constants
    const STATUS_PENDING = 'Pending';
    const STATUS_SUBMITTED = 'Submitted';
    const STATUS_PENDING_PAYROLL = 'Pending Payroll';
    const STATUS_APPROVED = 'Approved';
    const STATUS_REJECTED = 'Rejected';
    
    protected $table = 'employee_timesheet_status';

    protected $fillable = [
        'title',
        'color',
        'active_flag',
    ];

    protected $casts = [
        'active_flag' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get status ID by title (cached for performance)
     */
    public static function getIdByTitle(string $title): ?int
    {
        return cache()->remember(
            "timesheet_status_{$title}", 
            now()->addDay(), 
            fn() => static::where('title', $title)->value('id')
        );
    }
    
    /**
     * Convenience methods for common statuses
     */
    public static function pendingId(): ?int
    {
        return static::getIdByTitle(self::STATUS_PENDING);
    }
    
    public static function submittedId(): ?int
    {
        return static::getIdByTitle(self::STATUS_SUBMITTED);
    }
    
    public static function pendingPayrollId(): ?int
    {
        return static::getIdByTitle(self::STATUS_PENDING_PAYROLL);
    }
    
    public static function approvedId(): ?int
    {
        return static::getIdByTitle(self::STATUS_APPROVED);
    }
    
    public static function rejectedId(): ?int
    {
        return static::getIdByTitle(self::STATUS_REJECTED);
    }

    public function timesheets()
    {
        return $this->hasMany(EmployeeTimesheet::class, 'status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active_flag', 1);
    }
}
