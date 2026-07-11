@php
    /** @var \Spatie\Permission\Models\Role|null $role */
    $role = $role ?? null;
    $assignedPermissions = old('permissions', $role?->permissions->pluck('name')->all() ?? []);
@endphp

<div class="space-y-4">
    <x-form.input name="name" label="Role name" :value="$role?->name" required />

    <div>
        <p class="mb-1.5 block text-sm font-medium text-gray-700">Permissions</p>
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
