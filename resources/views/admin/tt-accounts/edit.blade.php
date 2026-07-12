<x-admin-layout :title="'Edit '.$ttAccount->title">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit TT Account</h1>
        <p class="mt-1 text-sm text-gray-500">Update the account details. Entries are managed on the account statement page.</p>
    </div>

    @include('admin.tt-accounts._form', [
        'action' => route('admin.tt-accounts.update', $ttAccount),
        'ttAccount' => $ttAccount,
    ])
</x-admin-layout>
