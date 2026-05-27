<?php

namespace App\Http\Controllers\MeridianHR;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends BaseHRController
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get()->map(fn($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'guard_name' => $p->guard_name,
            'created_at' => $p->created_at?->format('d M Y'),
        ]);

        $roles = Role::with('permissions')->orderBy('name')->get()->map(fn($r) => [
            'id'               => $r->id,
            'name'             => $r->name,
            'guard_name'       => $r->guard_name,
            'permission_ids'   => $r->permissions->pluck('id')->toArray(),
            'permissions_count'=> $r->permissions->count(),
            'created_at'       => $r->created_at?->format('d M Y'),
        ]);

        return Inertia::render('MeridianHR/RolesPermissions', array_merge(
            $this->getCommonProps('roles-permissions'),
            compact('permissions', 'roles')
        ));
    }

    public function storePermission(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:permissions,name']);
        Permission::create(['name' => $request->name, 'guard_name' => 'web']);
        return back()->with('success', 'Permission created.');
    }

    public function updatePermission(Request $request, $id)
    {
        $perm = Permission::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255|unique:permissions,name,' . $id]);
        $perm->update(['name' => $request->name]);
        return back()->with('success', 'Permission updated.');
    }

    public function destroyPermission($id)
    {
        Permission::findOrFail($id)->delete();
        return back()->with('success', 'Permission deleted.');
    }

    public function storeRole(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:roles,name']);
        Role::create(['name' => $request->name, 'guard_name' => 'web']);
        return back()->with('success', 'Role created.');
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255|unique:roles,name,' . $id]);
        $role->update(['name' => $request->name]);
        return back()->with('success', 'Role updated.');
    }

    public function destroyRole($id)
    {
        Role::findOrFail($id)->delete();
        return back()->with('success', 'Role deleted.');
    }

    public function syncRolePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'permission_ids'   => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);
        $role->syncPermissions($request->permission_ids ?? []);
        return back()->with('success', 'Permissions updated.');
    }
}
