<?php

namespace App\Http\Controllers\Ems;

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
                'global_statuses.name  as statusName',
                'global_statuses.color as statusColor',
            ])
            ->orderBy('venues.id', 'desc')
            ->get()
            ->map(fn($v) => [
                'id'          => $v->id,
                'title'       => $v->title,
                'activeFlag'  => $v->active_flag,
                'statusName'  => $v->statusName  ?? '',
                'statusColor' => $v->statusColor ?? '',
                'createdAt'   => $v->created_at?->format('Y-m-d'),
            ]);

        return Inertia::render('Venues/Index', [
            'venues'   => $venues,
            'statuses' => GlobalStatus::where('is_active', 1)->orderBy('name')->get(['id', 'name as title', 'color']),
        ]);
    }

    /** Bootstrap Table endpoint – GET /api/venues */
    public function data(Request $request)
    {
        $limit  = max(1, min((int) $request->input('limit', 10), 100));
        $offset = (int) $request->input('offset', 0);
        $order  = in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc'])
                    ? strtolower($request->input('order', 'desc'))
                    : 'desc';
        $sort   = $request->input('sort', 'id');
        $search = $request->input('search');

        $sortMap = [
            'id'          => 'venues.id',
            'title'        => 'venues.title',
            'status_name' => 'global_statuses.name',
            'created_at'  => 'venues.created_at',
            'updated_at'  => 'venues.updated_at',
        ];
        $sortCol = $sortMap[$sort] ?? 'venues.id';

        $query = Venue::query()
            ->leftJoin('global_statuses', 'venues.active_flag', '=', 'global_statuses.id')
            ->select([
                'venues.id',
                'venues.title',
                'venues.active_flag',
                'venues.created_at',
                'venues.updated_at',
                'global_statuses.name  as status_name',
                'global_statuses.color as status_color',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('venues.title', 'like', "%{$search}%")
                  ->orWhere('global_statuses.name', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortCol, $order);

        $total = $query->count();
        $rows  = $query->offset($offset)->limit($limit)->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'active_flag'  => $e->active_flag,
                'status_name'  => $e->status_name  ?? '',
                'status_color' => $e->status_color ?? '',
                'created_at'   => $e->created_at?->format('Y-m-d'),
                'updated_at'   => $e->updated_at?->format('Y-m-d'),
            ])
            ->values();

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /** GET /api/venues/all – flat list for dropdowns */
    public function all()
    {
        return response()->json(
            Venue::orderBy('title')->get(['id', 'title'])
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        Venue::create($data);

        return back()->with('success', 'Venue created successfully.');
    }

    public function update(Request $request, Venue $venue)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        $venue->update($data);

        return back()->with('success', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue)
    {
        $venue->delete();

        return response()->json(['message' => 'Venue deleted successfully.']);

        return back()->with('success', 'Venue deleted successfully.');
    }
}
