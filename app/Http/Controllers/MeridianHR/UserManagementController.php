<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserManagementController extends BaseHRController
{
    public function index()
    {
        // Get all users with their roles
        $users = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->join(', '),
                    'role_ids' => $user->roles->pluck('id')->toArray(),
                    'active_flag' => $user->active_flag ?? 1,
                    'created_at' => $user->created_at?->format('d M Y'),
                ];
            });

        // Get all available roles
        $roles = Role::orderBy('name')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
            ];
        });

        return Inertia::render('MeridianHR/ManagerUsers', array_merge(
            $this->getCommonProps('manager-users'),
            [
                'users' => $users,
                'roles' => $roles,
            ]
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'active_flag' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active_flag' => $request->active_flag ?? 1,
        ]);

        // Assign roles
        if ($request->role_ids) {
            $user->syncRoles($request->role_ids);
        }

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'active_flag' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Update active flag if provided
        if ($request->has('active_flag')) {
            $user->update([
                'active_flag' => $request->active_flag ? 1 : 0,
            ]);
        }

        // Update password if provided
        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Sync roles
        if ($request->has('role_ids')) {
            $user->syncRoles($request->role_ids ?? []);
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
