<x-admin-layout title="TT Accounts">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">TT Accounts</h1>
            <p class="mt-1 text-sm text-gray-500">Running debit/credit accounts per customer, like a bank statement.</p>
        </div>
        @can('tt-accounts.create')
            <x-button icon="plus" :href="route('admin.tt-accounts.create')">New TT Account</x-button>
        @endcan
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.tt-accounts.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search account title…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <select name="customer" onchange="this.form.submit()"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All customers</option>
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}" @selected($customerId === $customerOption->id)>{{ $customerOption->name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All statuses</option>
                @foreach (\App\Enums\TtAccountStatus::cases() as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>

        @if ($ttAccounts->isEmpty())
            <x-empty-state icon="book-open" title="No TT accounts found"
                           description="Click 'New TT Account' to open an account for a customer." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/75">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Account</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Currency</th>
                            <th class="px-4 py-3">Entries</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($ttAccounts as $ttAccount)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('admin.tt-accounts.show', $ttAccount) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $ttAccount->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.customers.show', $ttAccount->customer) }}" class="text-gray-600 hover:text-indigo-600">
                                        {{ $ttAccount->customer->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $ttAccount->currency->code }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $ttAccount->entries_count }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$ttAccount->status === \App\Enums\TtAccountStatus::Open ? 'green' : 'gray'">
                                        {{ $ttAccount->status->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.tt-accounts.show', $ttAccount) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        <a href="{{ route('admin.tt-accounts.print', $ttAccount) }}" target="_blank" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Print">
                                            <x-icon name="printer" class="size-4" />
                                        </a>
                                        @can('tt-accounts.edit')
                                            <a href="{{ route('admin.tt-accounts.edit', $ttAccount) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('tt-accounts.delete')
                                            <form method="POST" action="{{ route('admin.tt-accounts.destroy', $ttAccount) }}"
                                                  onsubmit="return confirm('Delete this TT account and all its entries?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                {{ $ttAccounts->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
