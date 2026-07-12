<x-admin-layout :title="$customer->name">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="flex size-14 items-center justify-center rounded-full bg-indigo-100 text-xl font-semibold text-indigo-700">
                {{ str($customer->name)->substr(0, 1)->upper() }}
            </span>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $customer->name }}</h1>
                    <x-badge :color="match ($customer->status->value) { 'approved' => 'green', 'pending' => 'yellow', default => 'red' }">
                        {{ $customer->status->label() }}
                    </x-badge>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">Customer since {{ $customer->created_at->format('d M Y') }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($customer->status === \App\Enums\UserStatus::Pending)
                @can('customers.approve')
                    <form method="POST" action="{{ route('admin.customers.approve', $customer) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" icon="check">Approve</x-button>
                    </form>
                    <form method="POST" action="{{ route('admin.customers.reject', $customer) }}"
                          onsubmit="return confirm('Reject this customer? They will not be able to log in.')">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="danger" icon="x-mark">Reject</x-button>
                    </form>
                @endcan
            @endif
            @can('customers.edit')
                <x-button variant="secondary" :href="route('admin.customers.edit', $customer)" icon="pencil">Edit</x-button>
                <x-button variant="secondary" icon="shield" x-data @click="$dispatch('open-modal', 'change-password')">
                    Change Password
                </x-button>
            @endcan
            @can('consignments.create')
                @if (Route::has('admin.customers.consignments.create'))
                    <x-button :href="route('admin.customers.consignments.create', $customer)" icon="plus">New Consignment</x-button>
                @endif
            @endcan
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Suppliers" :value="$customer->suppliers_count" icon="truck" color="sky" />
        <x-stat-card label="Consignments" :value="$customer->consignments_count" icon="cube" color="indigo" />
        <x-stat-card label="Purchase Items" :value="$purchaseItemsCount" icon="document" color="emerald" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Customer information --}}
        <x-card title="Customer Information" class="xl:col-span-1">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Email</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->email }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Phone</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Company</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->company_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Address</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->address ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Suppliers --}}
        @can('suppliers.view')
            <x-card title="Suppliers" :flush="true" class="xl:col-span-2">
                <x-slot:actions>
                    @can('suppliers.create')
                        <x-button icon="plus" x-data @click="$dispatch('open-modal', 'add-supplier')">Add Supplier</x-button>
                    @endcan
                </x-slot:actions>

                @if ($suppliers->isEmpty())
                    <x-empty-state icon="truck" title="No suppliers yet"
                                   description="Add suppliers for this customer to use them in consignments." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50/75">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 sm:px-6">Name</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Contact</th>
                                    <th class="px-4 py-3">Phone / WeChat</th>
                                    <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($suppliers as $supplier)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $supplier->name }}</td>
                                        <td class="px-4 py-3"><x-badge>{{ $supplier->category->name }}</x-badge></td>
                                        <td class="px-4 py-3 text-gray-600">{{ $supplier->contact_person ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $supplier->phone ?? '—' }}
                                            @if ($supplier->wechat)
                                                <span class="text-xs text-gray-400">/ {{ $supplier->wechat }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 sm:px-6">
                                            <div class="flex items-center justify-end gap-1">
                                                @can('suppliers.edit')
                                                    <button type="button" x-data @click="$dispatch('open-modal', 'edit-supplier-{{ $supplier->id }}')"
                                                            class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                        <x-icon name="pencil" class="size-4" />
                                                    </button>
                                                @endcan
                                                @can('suppliers.delete')
                                                    <form method="POST" action="{{ route('admin.customers.suppliers.destroy', [$customer, $supplier]) }}"
                                                          onsubmit="return confirm('Remove this supplier?')">
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
                    @if ($suppliers->hasPages())
                        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                            {{ $suppliers->links() }}
                        </div>
                    @endif
                @endif
            </x-card>
        @endcan
    </div>

    {{-- Consignments --}}
    @can('consignments.view')
        <div class="mt-6">
            <x-card title="Consignments" :flush="true">
                @if ($consignments->isEmpty())
                    <x-empty-state icon="cube" title="No consignments yet"
                                   description="Create a consignment to start recording purchases for this customer." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50/75">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 sm:px-6">Consignment No</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Currency</th>
                                    <th class="px-4 py-3">Items</th>
                                    <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($consignments as $consignment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium sm:px-6">
                                            @if (Route::has('admin.consignments.show'))
                                                <a href="{{ route('admin.consignments.show', $consignment) }}" class="text-gray-900 hover:text-indigo-600">{{ $consignment->consignment_no }}</a>
                                            @else
                                                {{ $consignment->consignment_no }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $consignment->consignment_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $consignment->currency->code }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $consignment->items_count }}</td>
                                        <td class="px-4 py-3 sm:px-6">
                                            <div class="flex items-center justify-end gap-1">
                                                @if (Route::has('admin.consignments.show'))
                                                    <a href="{{ route('admin.consignments.show', $consignment) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                                        <x-icon name="eye" class="size-4" />
                                                    </a>
                                                @endif
                                                @can('consignments.edit')
                                                    @if (Route::has('admin.consignments.edit'))
                                                        <a href="{{ route('admin.consignments.edit', $consignment) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                            <x-icon name="pencil" class="size-4" />
                                                        </a>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($consignments->hasPages())
                        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                            {{ $consignments->links() }}
                        </div>
                    @endif
                @endif
            </x-card>
        </div>
    @endcan

    {{-- Change password modal --}}
    @can('customers.edit')
        <x-modal name="change-password" title="Change Password" max-width="md">
            <form method="POST" action="{{ route('admin.customers.password.update', $customer) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="change-password">

                <p class="text-sm text-gray-500">
                    Set a new password for <span class="font-medium text-gray-900">{{ $customer->name }}</span>.
                    They will be logged out of any active sessions.
                </p>

                <x-form.input name="password" id="change-password-new" type="password" label="New password" required autocomplete="new-password" />
                <x-form.input name="password_confirmation" id="change-password-confirm" type="password" label="Confirm new password" required autocomplete="new-password" />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button type="submit">Change Password</x-button>
                </div>
            </form>
        </x-modal>
    @endcan

    {{-- Supplier modals --}}
    @can('suppliers.create')
        <x-modal name="add-supplier" title="Add Supplier" max-width="xl">
            @include('admin.customers._supplier-form', [
                'modalName' => 'add-supplier',
                'action' => route('admin.customers.suppliers.store', $customer),
                'supplier' => null,
            ])
        </x-modal>
    @endcan

    @can('suppliers.edit')
        @foreach ($suppliers as $supplier)
            <x-modal name="edit-supplier-{{ $supplier->id }}" title="Edit Supplier" max-width="xl">
                @include('admin.customers._supplier-form', [
                    'modalName' => 'edit-supplier-'.$supplier->id,
                    'action' => route('admin.customers.suppliers.update', [$customer, $supplier]),
                    'supplier' => $supplier,
                ])
            </x-modal>
        @endforeach
    @endcan
</x-admin-layout>
