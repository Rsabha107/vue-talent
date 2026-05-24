<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\EventTeamTemplate;
use App\Models\EventTeamTemplateRole;
use App\Models\Ems\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventTeamTemplateController extends BaseHRController
{
    public function index()
    {
        $templates = EventTeamTemplate::with('roles')
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'expectedTeamSize' => $template->expected_team_size,
                    'isActive' => $template->is_active,
                    'roleCount' => $template->roles->count(),
                    'roles' => $template->roles->sortBy('display_order')->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'roleName' => $role->role_name,
                            'suggestedCount' => $role->suggested_count,
                            'isRequired' => $role->is_required,
                        ];
                    })->values()->toArray(),
                    'createdAt' => $template->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('MeridianHR/EventTeamTemplate', array_merge(
            $this->getCommonProps('event-templates'),
            ['templates' => $templates]
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'expected_team_size' => 'nullable|integer|min:1',
            'roles' => 'required|array|min:1',
            'roles.*.role_name' => 'required|string|max:100',
            'roles.*.suggested_count' => 'required|integer|min:1',
            'roles.*.is_required' => 'boolean',
        ]);

        $template = EventTeamTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'expected_team_size' => $validated['expected_team_size'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id() ?? 1,
            'updated_by' => auth()->id() ?? 1,
        ]);

        foreach ($validated['roles'] as $index => $role) {
            EventTeamTemplateRole::create([
                'template_id' => $template->id,
                'role_name' => $role['role_name'],
                'suggested_count' => $role['suggested_count'],
                'is_required' => $role['is_required'] ?? false,
                'display_order' => $index,
            ]);
        }

        return back()->with('success', 'Template created successfully');
    }

    public function update(Request $request, EventTeamTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'expected_team_size' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'roles' => 'required|array|min:1',
            'roles.*.role_name' => 'required|string|max:100',
            'roles.*.suggested_count' => 'required|integer|min:1',
            'roles.*.is_required' => 'boolean',
        ]);

        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'expected_team_size' => $validated['expected_team_size'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => auth()->id() ?? 1,
        ]);

        // Delete existing roles and recreate
        $template->roles()->delete();
        
        foreach ($validated['roles'] as $index => $role) {
            EventTeamTemplateRole::create([
                'template_id' => $template->id,
                'role_name' => $role['role_name'],
                'suggested_count' => $role['suggested_count'],
                'is_required' => $role['is_required'] ?? false,
                'display_order' => $index,
            ]);
        }

        return back()->with('success', 'Template updated successfully');
    }

    public function destroy(EventTeamTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted successfully');
    }

    /**
     * Get templates for dropdown selection
     */
    public function list()
    {
        $templates = EventTeamTemplate::active()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'expectedTeamSize' => $template->expected_team_size,
                    'roleCount' => $template->roles->count(),
                    'roles' => $template->roles->sortBy('display_order')->values()->toArray(),
                ];
            });

        return response()->json(['templates' => $templates]);
    }

    /**
     * Apply a template to an event
     */
    public function applyToEvent(Request $request, Event $event)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:event_team_templates,id',
            'employee_assignments' => 'required|array',
            'employee_assignments.*.role_name' => 'required|string',
            'employee_assignments.*.employee_ids' => 'required|array',
            'employee_assignments.*.employee_ids.*' => 'exists:employees_all,id',
            'assigned_at' => 'required|date',
        ]);

        $template = EventTeamTemplate::findOrFail($validated['template_id']);
        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($validated['employee_assignments'] as $assignment) {
            foreach ($assignment['employee_ids'] as $empId) {
                // Check if already assigned
                $existing = $event->employees()->where('employee_id', $empId)->first();
                
                if ($existing && $existing->pivot->is_active == 1) {
                    $skippedCount++;
                    continue;
                }

                $pivotData = [
                    'assigned_at' => $validated['assigned_at'],
                    'event_role' => $assignment['role_name'],
                    'is_active' => 1,
                    'created_by' => auth()->id() ?? 1,
                    'updated_by' => auth()->id() ?? 1,
                ];

                if ($existing) {
                    // Reactivate
                    $event->employees()->updateExistingPivot($empId, array_merge($pivotData, [
                        'released_at' => null,
                    ]));
                } else {
                    // New assignment
                    $event->employees()->attach($empId, $pivotData);
                }
                
                $assignedCount++;
            }
        }

        $message = "{$assignedCount} employee(s) assigned from template \"{$template->name}\"";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} already assigned)";
        }

        return redirect()->route('hr.events.show', $event->id)->with('success', $message);
    }
}
