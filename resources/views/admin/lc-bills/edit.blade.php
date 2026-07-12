<x-admin-layout :title="'Edit LC Bill '.$lcBill->bill_no">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit LC Bill {{ $lcBill->bill_no }}</h1>
        <p class="mt-1 text-sm text-gray-500">Update the bill details and its received/paid entries.</p>
    </div>

    @include('admin.lc-bills._form', [
        'action' => route('admin.lc-bills.update', $lcBill),
        'lcBill' => $lcBill,
    ])
</x-admin-layout>
