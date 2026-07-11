<x-admin-layout title="Edit Consignment">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit Consignment {{ $consignment->consignment_no }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            For customer
            <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $customer->name }}</a>.
        </p>
    </div>

    @include('admin.consignments._form', [
        'action' => route('admin.consignments.update', $consignment),
    ])
</x-admin-layout>
