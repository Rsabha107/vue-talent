<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\GeneralSettings\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SettingsController extends BaseHRController
{
    /**
     * Display application settings page
     */
    public function index()
    {
        $settings = Setting::orderBy('key')->get()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ];
        });

        return Inertia::render('MeridianHR/Settings', array_merge($this->getCommonProps('application-settings'), [
            'settings' => $settings,
        ]));
    }

    /**
     * Store a new setting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'required|string',
        ]);

        Setting::create($validated);
        
        // Clear settings cache
        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Setting created successfully');
    }

    /**
     * Update an existing setting
     */
    public function update(Request $request, $id)
    {
        $setting = Setting::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings,key,' . $id,
            'value' => 'required|string',
        ]);

        $setting->update($validated);
        
        // Clear settings cache
        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Setting updated successfully');
    }

    /**
     * Delete a setting
     */
    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();
        
        // Clear settings cache
        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Setting deleted successfully');
    }
}
