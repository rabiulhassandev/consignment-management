<x-admin-layout title="Users">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Users</h1>
            <p class="mt-1 text-sm text-gray-500">Manage staff accounts and their access.</p>
        </div>
        <x-button :href="route('admin.users.create')" icon="plus">New User</x-button>
    </div>

    <x-card :flush="true">
        @if ($users->isEmpty())
            <x-empty-state icon="user-group" title="No staff users yet"
                           description="Create staff accounts and assign roles to control access." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($user->roles as $role)
                                            <x-badge color="indigo">{{ $role->name }}</x-badge>
                                        @empty
                                            <span class="text-xs text-gray-400">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                            <x-icon name="pencil" class="size-4" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                <x-icon name="trash" class="size-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
