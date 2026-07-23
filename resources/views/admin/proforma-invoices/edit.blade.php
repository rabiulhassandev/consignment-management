<x-admin-layout :title="'Edit Proforma Invoice '.$proformaInvoice->invoice_no">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit Proforma Invoice {{ $proformaInvoice->invoice_no }}</h1>
        <p class="mt-1 text-sm text-gray-500">Update the invoice details, shipping routing, and line items.</p>
    </div>

    @include('admin.proforma-invoices._form', [
        'action' => route('admin.proforma-invoices.update', $proformaInvoice),
        'proformaInvoice' => $proformaInvoice,
    ])
</x-admin-layout>
