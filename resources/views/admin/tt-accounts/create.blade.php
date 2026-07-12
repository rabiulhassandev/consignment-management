<x-admin-layout title="New TT Account">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">New TT Account</h1>
        <p class="mt-1 text-sm text-gray-500">Open a running debit/credit account for a customer.</p>
    </div>

    @include('admin.tt-accounts._form', [
        'action' => route('admin.tt-accounts.store'),
        'ttAccount' => null,
    ])
</x-admin-layout>
