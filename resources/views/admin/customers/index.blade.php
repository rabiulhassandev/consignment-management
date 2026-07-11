<x-admin-layout title="Customers">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Customers</h1>
        <p class="mt-1 text-sm text-gray-500">Review registrations and manage customer accounts.</p>
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.customers.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search name, email, company…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All statuses</option>
                @foreach (\App\Enums\UserStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($status === $case)>{{ $case->label() }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>

        @if ($customers->isEmpty())
            <x-empty-state icon="users" title="No customers found"
                           description="Customers who register will appear here for approval." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Customer</th>
                            <th class="px-4 py-3">Company</th>
                            <th class="px-4 py-3">Suppliers</th>
                            <th class="px-4 py-3">Consignments</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($customers as $customer)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $customer->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $customer->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $customer->company_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $customer->suppliers_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $customer->consignments_count }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="match ($customer->status->value) { 'approved' => 'green', 'pending' => 'yellow', default => 'red' }">
                                        {{ $customer->status->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $customer->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($customer->status === \App\Enums\UserStatus::Pending)
                                            @can('customers.approve')
                                                <form method="POST" action="{{ route('admin.customers.approve', $customer) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-green-50 hover:text-green-600" title="Approve">
                                                        <x-icon name="check" class="size-4" />
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.customers.reject', $customer) }}"
                                                      onsubmit="return confirm('Reject this customer? They will not be able to log in.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Reject">
                                                        <x-icon name="x-mark" class="size-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        @can('customers.edit')
                                            <a href="{{ route('admin.customers.edit', $customer) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                {{ $customers->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
