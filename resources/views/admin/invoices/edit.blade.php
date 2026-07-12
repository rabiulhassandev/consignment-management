<x-admin-layout :title="'Edit Invoice '.$invoice->invoice_no">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit Invoice {{ $invoice->invoice_no }}</h1>
        <p class="mt-1 text-sm text-gray-500">Update the invoice details and line items.</p>
    </div>

    @include('admin.invoices._form', [
        'action' => route('admin.invoices.update', $invoice),
        'invoice' => $invoice,
    ])
</x-admin-layout>
