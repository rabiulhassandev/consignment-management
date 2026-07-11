@php
    /** @var \App\Models\User|null $user */
    $user = $user ?? null;
    $assignedRoles = old('roles', $user?->roles->pluck('name')->all() ?? []);
    $assignedPermissions = old('permissions', $user?->permissions->pluck('name')->all() ?? []);
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="name" label="Full name" :value="$user?->name" required />
        <x-form.input name="email" type="email" label="Email address" :value="$user?->email" required />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="phone" label="Phone" :value="$user?->phone" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="password" type="password" :label="$user ? 'New password (leave blank to keep current)' : 'Password'" :required="$user === null" autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" :required="$user === null" autocomplete="new-password" />
    </div>

    <div>
        <p class="mb-1.5 block text-sm font-medium text-gray-700">Roles</p>
        <div class="flex flex-wrap gap-x-6 gap-y-2 rounded-lg border border-gray-200 p-4">
            @forelse ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                           @checked(in_array($role->name, $assignedRoles, true))
                           class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                    {{ $role->name }}
                </label>
            @empty
                <p class="text-sm text-gray-500">No roles defined yet.</p>
            @endforelse
        </div>
        @error('roles')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <p class="mb-1.5 block text-sm font-medium text-gray-700">Direct permissions <span class="font-normal text-gray-400">(in addition to role permissions)</span></p>
        <div class="space-y-4 rounded-lg border border-gray-200 p-4">
            @foreach ($permissionGroups as $group => $permissions)
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $group }}</p>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                       @checked(in_array($permission->name, $assignedPermissions, true))
                                       class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                {{ str($permission->name)->after('.') }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @error('permissions')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
