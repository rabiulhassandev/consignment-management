<x-portal-layout title="Dashboard">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Welcome, {{ $customer->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Here is an overview of your consignments.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card label="My Consignments" :value="$totalConsignments" icon="cube" />
        <x-stat-card label="Total Purchase Items" :value="$totalItems" icon="document" />
    </div>

    <div class="mt-6">
        <x-card title="Recent Consignments" :flush="true">
            @if ($recentConsignments->isEmpty())
                <x-empty-state icon="cube" title="No consignments yet"
                               description="Your consignments will appear here once created." />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentConsignments as $consignment)
                        <li class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                @if (Route::has('portal.consignments.show'))
                                    <a href="{{ route('portal.consignments.show', $consignment) }}" class="truncate text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $consignment->consignment_no }}</a>
                                @else
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $consignment->consignment_no }}</p>
                                @endif
                                <p class="truncate text-xs text-gray-500">{{ $consignment->consignment_date->format('d M Y') }} · {{ $consignment->currency->code }}</p>
                            </div>
                            <x-badge color="indigo">{{ $consignment->items_count }} {{ str('item')->plural($consignment->items_count) }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-portal-layout>
