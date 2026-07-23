<x-admin-layout title="New Proforma Invoice">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">New Proforma Invoice</h1>
        <p class="mt-1 text-sm text-gray-500">Issue an export proforma invoice with shipping routing and advising bank details.</p>
    </div>

    @include('admin.proforma-invoices._form', [
        'action' => route('admin.proforma-invoices.store'),
        'proformaInvoice' => null,
    ])
</x-admin-layout>
