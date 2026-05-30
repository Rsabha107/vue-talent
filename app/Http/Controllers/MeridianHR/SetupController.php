<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\MeridianHR\LookupTablesController;
use App\Models\Ems\Event;
use App\Models\Ems\Venue;
use App\Models\LeaveType;
use Inertia\Inertia;

class SetupController extends BaseHRController
{
    /**
     * Display the setup page with events and venues
     */
    public function index()
    {
        $events = Event::with('venues')
            ->where('active_flag', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'event_logo', 'created_at'])
            ->map(function ($event) {
                $eventModel = Event::find($event->id);
                $venues = $eventModel?->venues()->orderBy('title')->get(['venues.id', 'venues.title'])->toArray() ?? [];
                $employeeCount = $eventModel?->employees()->wherePivot('is_active', 1)->count() ?? 0;
                
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'event_logo' => $event->event_logo,
                    'logoUrl' => $event->event_logo ? asset('storage/event-logos/' . $event->event_logo) : null,
                    'venues' => $venues,
                    'venueIds' => array_column($venues, 'id'),
                    'employeeCount' => $employeeCount,
                    'createdAt' => $event->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        $venues = Venue::where('active_flag', 1)
            ->orderBy('title')
            ->get(['id', 'title', 'short_name']);
        
        $leaveTypes = LeaveType::where('active_flag', 1)
            ->orderBy('title')
            ->get(['id', 'title', 'active_flag', 'number_of_leaves', 'accrual_frequency', 'created_at'])
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'title' => $type->title,
                    'activeFlag' => $type->active_flag,
                    'numberOfLeaves' => $type->number_of_leaves,
                    'accrualFrequency' => $type->accrual_frequency,
                    'createdAt' => $type->created_at?->format('Y-m-d H:i:s'),
                ];
            });
        
        // Get counts for all lookup tables
        $lookupTablesController = new LookupTablesController();
        $lookupCounts = $lookupTablesController->getCounts();

        return Inertia::render('MeridianHR/Setup', array_merge($this->getCommonProps('setup'), [
            'events' => $events,
            'venues' => $venues,
            'leaveTypes' => $leaveTypes,
            'lookupCounts' => $lookupCounts,
        ]));
    }
}
