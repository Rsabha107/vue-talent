<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Ems\Venue;
use App\Models\GlobalStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VenueController extends BaseHRController
{
    public function index()
    {
        $venues = Venue::leftJoin('global_statuses', 'venues.active_flag', '=', 'global_statuses.id')
            ->select([
                'venues.id',
                'venues.title',
                'venues.active_flag',
                'venues.created_at',
                'venues.updated_at',
                'global_statuses.name as status_name',
                'global_statuses.color as status_color',
            ])
            ->orderBy('venues.id', 'desc')
            ->get()
            ->map(function ($venue) {
                return [
                    'id' => $venue->id,
                    'title' => $venue->title,
                    'activeFlag' => $venue->active_flag,
                    'statusName' => $venue->status_name ?? '',
                    'statusColor' => $venue->status_color ?? '',
                    'createdAt' => $venue->created_at?->format('Y-m-d H:i:s'),
                    'updatedAt' => $venue->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('MeridianHR/Venue', array_merge($this->getCommonProps('venues'), [
            'venues' => $venues,
            'statuses' => GlobalStatus::where('is_active', 1)->orderBy('name')->get(['id', 'name as title', 'color']),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'active_flag' => ['nullable', 'exists:global_statuses,id'],
        ]);
        
        // Default active_flag to 1 if not provided
        if (!isset($data['active_flag'])) {
            $data['active_flag'] = 1;
        }
        
        // Set created_by and updated_by
        $data['created_by'] = auth()->user()?->id ?? 1;
        $data['updated_by'] = auth()->user()?->id ?? 1;

        Venue::create($data);

        return back()->with('success', 'Venue created successfully');
    }

    public function update(Request $request, Venue $venue)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'active_flag' => ['nullable', 'exists:global_statuses,id'],
        ]);
        
        // Keep existing active_flag if not provided
        if (!isset($data['active_flag'])) {
            unset($data['active_flag']);
        }
        
        // Update updated_by
        $data['updated_by'] = auth()->user()?->id ?? 1;

        $venue->update($data);

        return back()->with('success', 'Venue updated successfully');
    }

    public function destroy(Venue $venue)
    {
        $venue->delete();

        return back()->with('success', 'Venue deleted successfully');
    }
}
