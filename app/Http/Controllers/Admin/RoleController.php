<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * List roles.
     */
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Store a new role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Update a role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        if ($role->name === RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE) {
            return back()->with('error', 'The Super Admin role cannot be modified.');
        }

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE) {
            return back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'This role is assigned to users and cannot be deleted.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Permissions grouped by module for the assignment checkboxes.
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    private function permissionGroups(): Collection
    {
        return Permission::orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->title()->toString());
    }
}
