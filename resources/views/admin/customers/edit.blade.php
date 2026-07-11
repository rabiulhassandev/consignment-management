<x-admin-layout title="Edit Customer">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit Customer</h1>
        <p class="mt-1 text-sm text-gray-500">Update {{ $customer->name }}'s basic information.</p>
    </div>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Full name" :value="$customer->name" required />
                <x-form.input name="email" type="email" label="Email address" :value="$customer->email" required />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="phone" label="Phone" :value="$customer->phone" />
                <x-form.input name="company_name" label="Company name" :value="$customer->company_name" />
            </div>

            <x-form.input name="address" label="Address" :value="$customer->address" />

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-button variant="secondary" :href="route('admin.customers.show', $customer)">Cancel</x-button>
                <x-button type="submit">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</x-admin-layout>
