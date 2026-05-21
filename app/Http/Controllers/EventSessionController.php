<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventSessionController extends Controller
{
    public function setEvent(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|integer|exists:events,id',
        ]);

        $request->session()->put('selected_event_id', $validated['event_id']);

        return back()->with('success', 'Event selected successfully');
    }

    public function clearEvent(Request $request)
    {
        $request->session()->forget('selected_event_id');

        return back()->with('success', 'Event selection cleared');
    }
}
