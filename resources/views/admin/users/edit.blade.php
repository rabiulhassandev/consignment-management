<x-admin-layout title="Edit User">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit User</h1>
        <p class="mt-1 text-sm text-gray-500">Update {{ $user->name }}'s account and access.</p>
    </div>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('admin.users._form')

            <div class="mt-6 flex items-center justify-end gap-3">
                <x-button variant="secondary" :href="route('admin.users.index')">Cancel</x-button>
                <x-button type="submit">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</x-admin-layout>
