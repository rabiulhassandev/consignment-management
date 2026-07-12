<x-admin-layout title="New Invoice">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">New Invoice</h1>
        <p class="mt-1 text-sm text-gray-500">Create a quick product bill for an instant customer.</p>
    </div>

    @include('admin.invoices._form', [
        'action' => route('admin.invoices.store'),
        'invoice' => null,
    ])
</x-admin-layout>
