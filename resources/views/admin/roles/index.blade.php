<x-admin-layout title="Roles">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Roles</h1>
            <p class="mt-1 text-sm text-gray-500">Define roles and the permissions they grant.</p>
        </div>
        <x-button :href="route('admin.roles.create')" icon="plus">New Role</x-button>
    </div>

    <x-card :flush="true">
        @if ($roles->isEmpty())
            <x-empty-state icon="shield" title="No roles yet" description="Create roles to group permissions for your team." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Role</th>
                            <th class="px-4 py-3">Permissions</th>
                            <th class="px-4 py-3">Users</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($roles as $role)
                            @php
                                $isSuperAdmin = $role->name === \Database\Seeders\RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">
                                    <div class="flex items-center gap-2">
                                        {{ $role->name }}
                                        @if ($isSuperAdmin)
                                            <x-badge color="indigo">All access</x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $isSuperAdmin ? 'All' : $role->permissions_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $role->users_count }}</td>
                                <td class="px-4 py-3 sm:px-6">
                                    @unless ($isSuperAdmin)
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                                  onsubmit="return confirm('Delete this role?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            </form>
                                        </div>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                {{ $roles->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
