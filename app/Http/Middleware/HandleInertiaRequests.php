<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
            'dateFormat' => config('settings.date_format', 'DD/MM/YYYY'),
            'availableEvents' => fn() => \App\Models\Ems\Event::where('active_flag', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'event_logo'])
                ->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'logo' => $e->event_logo ? asset('storage/event-logos/' . $e->event_logo) : null,
                ]),
            'selectedEvent' => fn() => $request->session()->get('selected_event_id'),
        ];
    }
}
