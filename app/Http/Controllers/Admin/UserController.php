<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List staff users.
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::staff()->with('roles')->latest()->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new staff user.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Store a new staff user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->syncRoles($validated['roles'] ?? []);
        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing a staff user.
     */
    public function edit(User $user): View
    {
        abort_unless($user->isStaff(), 404);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Update a staff user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->syncRoles($validated['roles'] ?? []);
        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete a staff user.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
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
            ->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->headline()->toString());
    }
}
