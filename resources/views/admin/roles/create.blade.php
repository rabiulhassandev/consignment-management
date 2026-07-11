<x-admin-layout title="New Role">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">New Role</h1>
        <p class="mt-1 text-sm text-gray-500">Create a role and choose which permissions it grants.</p>
    </div>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            @include('admin.roles._form')

            <div class="mt-6 flex items-center justify-end gap-3">
                <x-button variant="secondary" :href="route('admin.roles.index')">Cancel</x-button>
                <x-button type="submit">Create Role</x-button>
            </div>
        </form>
    </x-card>
</x-admin-layout>
