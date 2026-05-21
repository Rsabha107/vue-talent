<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\Ems\Venue;
use App\Models\GlobalStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VenueController extends Controller
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

        return Inertia::render('MeridianHR/Venue', [
            'hrRole' => request()->query('role', 'admin'),
            'venues' => $venues,
            'statuses' => GlobalStatus::where('is_active', 1)->orderBy('name')->get(['id', 'name as title', 'color']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        Venue::create($data);

        return back()->with('success', 'Venue created successfully');
    }

    public function update(Request $request, Venue $venue)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        $venue->update($data);

        return back()->with('success', 'Venue updated successfully');
    }

    public function destroy(Venue $venue)
    {
        $venue->delete();

        return back()->with('success', 'Venue deleted successfully');
    }
}
