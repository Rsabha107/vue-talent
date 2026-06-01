<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Employee;
use App\Models\Ems\Event;
use App\Models\Ems\Venue;
use App\Models\GlobalStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventController extends BaseHRController
{
    public function index()
    {
        $events = Event::with('venues')
            ->leftJoin('global_statuses', 'events.active_flag', '=', 'global_statuses.id')
            ->select([
                'events.id',
                'events.name',
                'events.event_logo',
                'events.active_flag',
                'events.created_at',
                'events.updated_at',
                'global_statuses.name as status_name',
                'global_statuses.color as status_color',
            ])
            ->orderBy('events.id', 'desc')
            ->get()
            ->map(function ($event) {
                $eventModel = Event::find($event->id);
                $venues = $eventModel?->venues()->orderBy('title')->get(['venues.id', 'venues.title'])->toArray() ?? [];
                $employeeCount = $eventModel?->employees()->wherePivot('is_active', 1)->count() ?? 0;
                
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'eventLogo' => $event->event_logo,
                    'logoUrl' => $event->event_logo ? asset('storage/event-logos/' . $event->event_logo) : null,
                    'activeFlag' => $event->active_flag,
                    'statusName' => $event->status_name ?? '',
                    'statusColor' => $event->status_color ?? '',
                    'createdAt' => $event->created_at?->format('Y-m-d H:i:s'),
                    'updatedAt' => $event->updated_at?->format('Y-m-d H:i:s'),
                    'venues' => $venues,
                    'venueIds' => array_column($venues, 'id'),
                    'employeeCount' => $employeeCount,
                ];
            });

        return Inertia::render('MeridianHR/Event', array_merge($this->getCommonProps('events'), [
            'events' => $events,
            'venues' => Venue::orderBy('title')->get(['id', 'title']),
            'statuses' => GlobalStatus::where('is_active', 1)->orderBy('name')->get(['id', 'name as title', 'color']),
        ]));
    }

    public function show(Event $event)
    {
        $venues = $event->venues()->orderBy('title')->get(['venues.id', 'venues.title'])->toArray();
        
        $assignedEmployees = $event->employees()
            ->wherePivot('is_active', 1)
            ->with(['department', 'designation'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'email' => $emp->work_email_address ?? $emp->personal_email_address,
                    'empNumber' => $emp->employee_number,
                    'department' => $emp->department?->name,
                    'designation' => $emp->designation?->name,
                    'assignedAt' => $emp->pivot->assigned_at,
                ];
            });

        $assignedIds = $assignedEmployees->pluck('id');

        $availableEmployees = Employee::active()
            ->whereNotIn('id', $assignedIds)
            ->with(['department', 'designation'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'empNumber' => $emp->employee_number,
                    'department' => $emp->department?->name,
                ];
            });

        return Inertia::render('MeridianHR/EventDetail', array_merge(
            $this->getCommonProps('event-detail'),
            [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'eventLogo' => $event->event_logo,
                    'logoUrl' => $event->event_logo ? asset('storage/event-logos/' . $event->event_logo) : null,
                    'activeFlag' => $event->active_flag,
                    'statusName' => $event->activeStatus?->name ?? 'Unknown',
                    'statusColor' => $event->activeStatus?->color ?? '#999',
                    'createdAt' => $event->created_at?->format('Y-m-d H:i:s'),
                    'updatedAt' => $event->updated_at?->format('Y-m-d H:i:s'),
                    'venues' => $venues,
                ],
                'assignedEmployees' => $assignedEmployees,
                'availableEmployees' => $availableEmployees,
            ]
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active_flag' => ['nullable', 'exists:global_statuses,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'venue_ids' => ['nullable', 'array'],
            'venue_ids.*' => ['exists:venues,id'],
        ]);

        $venueIds = $data['venue_ids'] ?? [];
        unset($data['venue_ids'], $data['logo']);
        
        // Default active_flag to 1 if not provided
        if (!isset($data['active_flag'])) {
            $data['active_flag'] = 1;
        }

        if ($request->hasFile('logo')) {
            $data['event_logo'] = basename($request->file('logo')->store('event-logos', 'public'));
        }

        $event = Event::create($data);

        if (!empty($venueIds)) {
            $event->venues()->sync($venueIds);
        }

        return back()->with('success', 'Event created successfully');
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active_flag' => ['nullable', 'exists:global_statuses,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'venue_ids' => ['nullable', 'array'],
            'venue_ids.*' => ['exists:venues,id'],
        ]);

        $venueIds = $data['venue_ids'] ?? [];
        unset($data['venue_ids'], $data['logo']);
        
        // Keep existing active_flag if not provided
        if (!isset($data['active_flag'])) {
            unset($data['active_flag']);
        }

        if ($request->hasFile('logo')) {
            if ($event->event_logo) {
                Storage::disk('public')->delete('event-logos/' . $event->event_logo);
            }
            $data['event_logo'] = basename($request->file('logo')->store('event-logos', 'public'));
        }

        $event->update($data);

        $event->venues()->sync($venueIds);

        return back()->with('success', 'Event updated successfully');
    }

    public function destroy(Event $event)
    {
        if ($event->event_logo) {
            Storage::disk('public')->delete('event-logos/' . $event->event_logo);
        }

        $event->venues()->detach();
        $event->delete();

        return back()->with('success', 'Event deleted successfully');
    }

    /**
     * Get employees assigned to a specific event
     */
    public function showEmployees(Event $event)
    {
        $employees = $event->employees()
            ->wherePivot('is_active', 1)
            ->with(['department', 'designation'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'email' => $emp->work_email_address ?? $emp->personal_email_address,
                    'empNumber' => $emp->employee_number,
                    'department' => $emp->department?->name,
                    'designation' => $emp->designation?->name,
                    'assignedAt' => $emp->pivot->assigned_at,
                    'releasedAt' => $emp->pivot->released_at,
                ];
            });

        return response()->json([
            'employees' => $employees,
            'count' => $employees->count(),
        ]);
    }

    /**
     * Assign employees to an event
     */
    public function assignEmployees(Request $request, Event $event)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees_all,id',
            'assigned_at' => 'required|date',
        ]);

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($validated['employee_ids'] as $empId) {
            // Check if already assigned
            $existing = $event->employees()->where('employee_id', $empId)->first();
            
            if ($existing) {
                // If previously released, reactivate
                if ($existing->pivot->is_active == 0) {
                    $event->employees()->updateExistingPivot($empId, [
                        'is_active' => 1,
                        'assigned_at' => $validated['assigned_at'],
                        'released_at' => null,
                    ]);
                    $assignedCount++;
                } else {
                    $skippedCount++;
                }
            } else {
                // New assignment
                $event->employees()->attach($empId, [
                    'assigned_at' => $validated['assigned_at'],
                    'is_active' => 1,
                    'created_by' => auth()->id() ?? 1,
                    'updated_by' => auth()->id() ?? 1,
                ]);
                $assignedCount++;
            }
        }

        $message = $assignedCount > 0 
            ? "{$assignedCount} employee(s) assigned to event" 
            : "No new assignments made";
        
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} already assigned)";
        }

        return redirect()->route('hr.events.show', $event->id)->with('success', $message);
    }

    /**
     * Remove (deactivate) an employee from an event
     */
    public function removeEmployee(Event $event, Employee $employee)
    {
        $event->employees()->updateExistingPivot($employee->id, [
            'is_active' => 0,
            'released_at' => now(),
            'updated_by' => auth()->id() ?? 1,
        ]);

        return redirect()->route('hr.events.show', $event->id)->with('success', 'Employee removed from event');
    }

    /**
     * Get employees not assigned to a specific event
     */
    public function unassignedEmployees(Event $event)
    {
        $assignedIds = $event->employees()
            ->wherePivot('is_active', 1)
            ->pluck('employees_all.id');

        $employees = Employee::active()
            ->whereNotIn('id', $assignedIds)
            ->with(['department', 'designation'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'email' => $emp->work_email_address ?? $emp->personal_email_address,
                    'empNumber' => $emp->employee_number,
                    'department' => $emp->department?->name,
                    'designation' => $emp->designation?->name,
                ];
            });

        return response()->json(['employees' => $employees]);
    }

    /**
     * Get list of events that can be used as source for copying team
     */
    public function listSourceEvents(Event $targetEvent)
    {
        try {
            $events = Event::query()
                ->where('id', '!=', $targetEvent->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($event) {
                    // Safely load venues
                    $venueNames = '';
                    try {
                        $event->load('venues');
                        $venueNames = $event->venues->pluck('title')->implode(', ');
                    } catch (\Exception $e) {
                        // Ignore venue loading errors
                    }
                    
                    // Safely count employees
                    $teamCount = 0;
                    try {
                        $teamCount = $event->employees()->wherePivot('is_active', 1)->count();
                    } catch (\Exception $e) {
                        // Ignore counting errors
                    }
                    
                    return [
                        'id' => $event->id,
                        'name' => $event->name ?? 'Unnamed Event',
                        'teamCount' => $teamCount,
                        'venues' => $venueNames,
                        'createdAt' => $event->created_at?->format('M d, Y') ?? '',
                    ];
                });

            return response()->json(['events' => $events]);
        } catch (\Exception $e) {
            Log::error('Error in listSourceEvents: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage(), 'events' => []], 500);
        }
    }

    /**
     * Copy team from another event
     */
    public function copyTeamFrom(Request $request, Event $event)
    {
        $validated = $request->validate([
            'source_event_id' => 'required|exists:events,id',
            'assigned_at' => 'required|date',
        ]);

        $sourceEvent = Event::findOrFail($validated['source_event_id']);
        
        $sourceEmployees = $sourceEvent->employees()
            ->wherePivot('is_active', 1)
            ->with(['department', 'designation'])
            ->get();

        $copiedCount = 0;
        $skippedCount = 0;

        foreach ($sourceEmployees as $emp) {
            // Check if already assigned to target event
            $existing = $event->employees()->where('employee_id', $emp->id)->first();
            
            if ($existing && $existing->pivot->is_active == 1) {
                $skippedCount++;
                continue;
            }

            $pivotData = [
                'event_id' => $event->id,
                'employee_id' => $emp->id,
                'assigned_at' => $validated['assigned_at'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ];

            if ($existing) {
                // Reactivate previously released employee
                $event->employees()->updateExistingPivot($emp->id, array_merge($pivotData, [
                    'released_at' => null,
                ]));
            } else {
                // New assignment - use DB insert directly
                DB::table('employee_events')->insert($pivotData);
            }
            
            $copiedCount++;
        }

        $message = "{$copiedCount} employee(s) copied from {$sourceEvent->name}";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} already assigned)";
        }

        return redirect()->route('hr.events.show', $event->id)->with('success', $message);
    }

    /**
     * Import employees from CSV file
     */
    public function importFromCsv(Request $request, Event $event)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'assigned_at' => 'required|date',
        ]);

        $file = $request->file('csv_file');
        $assignedAt = $request->input('assigned_at');
        
        $importedCount = 0;
        $skippedCount = 0;
        $notFoundCount = 0;
        $errors = [];

        if (($handle = fopen($file->path(), 'r')) !== false) {
            $header = fgetcsv($handle); // Skip header row
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                if (empty($data[0])) {
                    continue; // Skip empty rows
                }

                $employeeNumber = trim($data[0]);
                $eventRole = isset($data[1]) ? trim($data[1]) : null;

                // Find employee by employee_number
                $employee = Employee::where('employee_number', $employeeNumber)
                    ->where('is_active', 1)
                    ->first();

                if (!$employee) {
                    $notFoundCount++;
                    $errors[] = "Row {$rowNumber}: Employee '{$employeeNumber}' not found";
                    continue;
                }

                // Check if already assigned
                $existing = $event->employees()->where('employee_id', $employee->id)->first();
                
                if ($existing && $existing->pivot->is_active == 1) {
                    $skippedCount++;
                    continue;
                }

                $pivotData = [
                    'event_id' => $event->id,
                    'employee_id' => $employee->id,
                    'assigned_at' => $assignedAt,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => auth()->id() ?? 1,
                    'updated_by' => auth()->id() ?? 1,
                ];

                if ($existing) {
                    // Reactivate previously released employee
                    $event->employees()->updateExistingPivot($employee->id, array_merge($pivotData, [
                        'released_at' => null,
                    ]));
                } else {
                    // New assignment
                    DB::table('employee_events')->insert($pivotData);
                }
                
                $importedCount++;
            }

            fclose($handle);
        }

        $message = "{$importedCount} employee(s) imported successfully";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} already assigned)";
        }
        if ($notFoundCount > 0) {
            $message .= " ({$notFoundCount} not found)";
        }

        return redirect()->route('hr.events.show', $event->id)
            ->with('success', $message)
            ->with('import_errors', $errors);
    }
}

