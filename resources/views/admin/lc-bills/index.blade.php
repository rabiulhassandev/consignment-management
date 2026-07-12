<x-admin-layout title="LC Bills">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">LC Bills</h1>
            <p class="mt-1 text-sm text-gray-500">Container consignment bills with received and paid accounts per LC.</p>
        </div>
        @can('lc-bills.create')
            <x-button icon="plus" :href="route('admin.lc-bills.create')">New LC Bill</x-button>
        @endcan
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.lc-bills.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search bill no or LC number…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <select name="customer" onchange="this.form.submit()"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All customers</option>
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}" @selected($customerId === $customerOption->id)>{{ $customerOption->name }}</option>
                @endforeach
            </select>
            <select name="settled" onchange="this.form.submit()"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All statuses</option>
                <option value="1" @selected($settled === '1')>Settled</option>
                <option value="0" @selected($settled === '0')>Unsettled</option>
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>

        @if ($lcBills->isEmpty())
            <x-empty-state icon="banknotes" title="No LC bills found"
                           description="Click 'New LC Bill' to open a bill for a customer's LC." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/75">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Bill No</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">LC Number</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Received</th>
                            <th class="px-4 py-3 text-right">Paid</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($lcBills as $lcBill)
                            @php
                                $received = (float) ($lcBill->received_sum ?? 0);
                                $paid = (float) ($lcBill->paid_sum ?? 0);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('admin.lc-bills.show', $lcBill) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $lcBill->bill_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.customers.show', $lcBill->customer) }}" class="text-gray-600 hover:text-indigo-600">
                                        {{ $lcBill->customer->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $lcBill->lc_number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $lcBill->bill_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($received, 2) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($paid, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium {{ $received - $paid < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $lcBill->currency->symbol }}{{ number_format($received - $paid, 2) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $lcBill->currency->code }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$lcBill->is_settled ? 'green' : 'yellow'">
                                        {{ $lcBill->is_settled ? 'Settled' : 'Unsettled' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.lc-bills.show', $lcBill) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        <a href="{{ route('admin.lc-bills.print', $lcBill) }}" target="_blank" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Print">
                                            <x-icon name="printer" class="size-4" />
                                        </a>
                                        @can('lc-bills.edit')
                                            <a href="{{ route('admin.lc-bills.edit', $lcBill) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('lc-bills.delete')
                                            <form method="POST" action="{{ route('admin.lc-bills.destroy', $lcBill) }}"
                                                  onsubmit="return confirm('Delete this LC bill and all its entries?')">
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
                {{ $lcBills->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
