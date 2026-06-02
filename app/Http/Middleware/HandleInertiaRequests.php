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
            'appName' => fn() => $this->getAppName(),
            'dateFormat' => config('settings.date_format', 'DD/MM/YYYY'),
            'availableEvents' => fn() => $this->getAvailableEventsForUser($request->user()),
            'selectedEvent' => fn() => $request->session()->get('selected_event_id'),
        ];
    }

    /**
     * Get available events based on user role
     * Admin/Manager: All active events
     * Employees: Only assigned events
     */
    protected function getAvailableEventsForUser($user)
    {
        if (!$user) {
            return collect([]);
        }

        // Check if user has admin or manager role
        $isAdminOrManager = $user->hasAnyRole(['admin', 'administrator', 'hr-admin', 'manager', 'supervisor']);

        if ($isAdminOrManager) {
            // Admin/Manager see all events
            return \App\Models\Ems\Event::where('active_flag', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'event_logo'])
                ->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'logo' => $e->event_logo ? asset('storage/event-logos/' . $e->event_logo) : null,
                ]);
        }

        // Employees see only their assigned events
        $employee = \App\Models\Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return collect([]);
        }

        return $employee->events()
            ->where('events.active_flag', 1)
            ->where('employee_events.is_active', 1)
            ->orderBy('name')
            ->get(['events.id', 'events.name', 'events.event_logo'])
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'logo' => $e->event_logo ? asset('storage/event-logos/' . $e->event_logo) : null,
            ]);
    }

    /**
     * Get application name from settings
     */
    protected function getAppName()
    {
        return \App\Models\GeneralSettings\Setting::where('key', 'app_name')
            ->value('value') ?? 'Meridian HR';
    }
}
