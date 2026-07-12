<x-admin-layout title="Dashboard">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Overview of customers, suppliers and consignments.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Customers" :value="$totalCustomers" icon="users" color="indigo" />
        <x-stat-card label="Pending Approvals" :value="$pendingCustomers" icon="bell" color="amber" />
        <x-stat-card label="Suppliers" :value="$totalSuppliers" icon="truck" color="sky" />
        <x-stat-card label="Consignments" :value="$totalConsignments" icon="cube" color="emerald" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="Recent Customers" :flush="true">
            @if ($recentCustomers->isEmpty())
                <x-empty-state icon="users" title="No customers yet"
                               description="Customers who register will appear here." />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentCustomers as $customer)
                        <li class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                @if (Route::has('admin.customers.show'))
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="truncate text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $customer->name }}</a>
                                @else
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $customer->name }}</p>
                                @endif
                                <p class="truncate text-xs text-gray-500">{{ $customer->email }}</p>
                            </div>
                            <x-badge :color="match ($customer->status->value) { 'approved' => 'green', 'pending' => 'yellow', default => 'red' }">
                                {{ $customer->status->label() }}
                            </x-badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Recent Consignments" :flush="true">
            @if ($recentConsignments->isEmpty())
                <x-empty-state icon="cube" title="No consignments yet"
                               description="Create a consignment from a customer's profile." />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentConsignments as $consignment)
                        <li class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                @if (Route::has('admin.consignments.show'))
                                    <a href="{{ route('admin.consignments.show', $consignment) }}" class="truncate text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $consignment->consignment_no }}</a>
                                @else
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $consignment->consignment_no }}</p>
                                @endif
                                <p class="truncate text-xs text-gray-500">
                                    {{ $consignment->customer->name }} · {{ $consignment->consignment_date->format('d M Y') }}
                                </p>
                            </div>
                            <x-badge color="indigo">{{ $consignment->items_count }} {{ str('item')->plural($consignment->items_count) }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-admin-layout>
