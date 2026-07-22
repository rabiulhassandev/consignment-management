<x-admin-layout :title="'Edit Sales Contract '.$salesContract->contract_no">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit Sales Contract {{ $salesContract->contract_no }}</h1>
        <p class="mt-1 text-sm text-gray-500">Update the contract details, line items, and terms.</p>
    </div>

    @include('admin.sales-contracts._form', [
        'action' => route('admin.sales-contracts.update', $salesContract),
        'salesContract' => $salesContract,
    ])
</x-admin-layout>
