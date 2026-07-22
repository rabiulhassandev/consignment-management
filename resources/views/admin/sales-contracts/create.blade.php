<x-admin-layout title="New Sales Contract">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">New Sales Contract</h1>
        <p class="mt-1 text-sm text-gray-500">Draw up a sales contract with H.S. codes, units, and freight.</p>
    </div>

    @include('admin.sales-contracts._form', [
        'action' => route('admin.sales-contracts.store'),
        'salesContract' => null,
    ])
</x-admin-layout>
