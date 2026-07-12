<x-admin-layout title="New LC Bill">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">New LC Bill</h1>
        <p class="mt-1 text-sm text-gray-500">Open a container consignment bill for a customer's LC.</p>
    </div>

    @include('admin.lc-bills._form', [
        'action' => route('admin.lc-bills.store'),
        'lcBill' => null,
    ])
</x-admin-layout>
