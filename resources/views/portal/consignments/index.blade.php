<x-portal-layout title="My Consignments">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">My Consignments</h1>
        <p class="mt-1 text-sm text-gray-500">All consignments recorded for your account.</p>
    </div>

    <x-card :flush="true">
        @if ($consignments->isEmpty())
            <x-empty-state icon="cube" title="No consignments yet"
                           description="Your consignments will appear here once they are created." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Consignment No</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($consignments as $consignment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('portal.consignments.show', $consignment) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $consignment->consignment_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $consignment->consignment_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $consignment->items_count }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $consignment->currency->symbol }}{{ number_format((float) ($consignment->items_sum_amount ?? 0), 2) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $consignment->currency->code }}</span>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end">
                                        <a href="{{ route('portal.consignments.show', $consignment) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                {{ $consignments->links() }}
            </div>
        @endif
    </x-card>
</x-portal-layout>
