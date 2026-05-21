<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\Ems\Event;
use App\Models\Ems\Venue;
use App\Models\GlobalStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventController extends Controller
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
                $venues = Event::find($event->id)?->venues()->orderBy('title')->get(['venues.id', 'venues.title'])->toArray() ?? [];
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
                ];
            });

        return Inertia::render('MeridianHR/Event', [
            'hrRole' => request()->query('role', 'admin'),
            'events' => $events,
            'venues' => Venue::orderBy('title')->get(['id', 'title']),
            'statuses' => GlobalStatus::where('is_active', 1)->orderBy('name')->get(['id', 'name as title', 'color']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'venue_ids' => ['nullable', 'array'],
            'venue_ids.*' => ['exists:venues,id'],
        ]);

        $venueIds = $data['venue_ids'] ?? [];
        unset($data['venue_ids'], $data['logo']);

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
            'active_flag' => ['required', 'exists:global_statuses,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'venue_ids' => ['nullable', 'array'],
            'venue_ids.*' => ['exists:venues,id'],
        ]);

        $venueIds = $data['venue_ids'] ?? [];
        unset($data['venue_ids'], $data['logo']);

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
}
