<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | The date format used throughout the application for displaying dates.
    | This format is used by v-calendar DatePicker components.
    |
    | Supported formats:
    | - DD/MM/YYYY (European format, e.g., 24/05/2026)
    | - MM/DD/YYYY (US format, e.g., 05/24/2026)
    | - YYYY-MM-DD (ISO format, e.g., 2026-05-24)
    | - DD-MM-YYYY (e.g., 24-05-2026)
    | - DD MMM YYYY (e.g., 24 May 2026)
    | - MMM DD, YYYY (e.g., May 24, 2026)
    |
    */

    'date_format' => env('DATE_FORMAT', 'DD/MM/YYYY'),

    /*
    |--------------------------------------------------------------------------
    | Timesheet Submission Cutoff Day
    |--------------------------------------------------------------------------
    |
    | The day of the month after which timesheet submissions are closed.
    | Set to 0 to disable cutoff (submissions always open).
    |
    */

    
    // Short alias for convenience
    // 'timesheet_cutoff_day' => env('TIMESHEET_SUBMISSION_CUTOFF_DAY', 21),

];
