@php use App\Enums\EntryType; use App\Enums\TtAccountStatus; @endphp

<x-admin-layout :title="$ttAccount->title">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $ttAccount->title }}</h1>
                <x-badge color="indigo">{{ $ttAccount->currency->code }}</x-badge>
                <x-badge :color="$ttAccount->status === TtAccountStatus::Open ? 'green' : 'gray'">
                    {{ $ttAccount->status->label() }}
                </x-badge>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                <a href="{{ route('admin.customers.show', $ttAccount->customer) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $ttAccount->customer->name }}</a>
                · {{ $ttAccount->entries->count() }} {{ str('entry')->plural($ttAccount->entries->count()) }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('tt-accounts.edit')
                <x-button icon="plus" x-data @click="$dispatch('open-modal', 'add-entry')">Add Entry</x-button>
            @endcan
            <x-button variant="secondary" :href="route('admin.tt-accounts.print', $ttAccount)" target="_blank" icon="printer">Print</x-button>
            @can('tt-accounts.edit')
                <x-button variant="secondary" :href="route('admin.tt-accounts.edit', $ttAccount)" icon="pencil">Edit</x-button>
            @endcan
            @can('tt-accounts.delete')
                <form method="POST" action="{{ route('admin.tt-accounts.destroy', $ttAccount) }}"
                      onsubmit="return confirm('Delete this TT account and all its entries?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Received" :value="$ttAccount->currency->symbol.number_format($totalReceived, 2)" icon="banknotes" color="emerald" />
        <x-stat-card label="Total Paid" :value="$ttAccount->currency->symbol.number_format($totalPaid, 2)" icon="banknotes" color="rose" />
        <x-stat-card label="Current Balance" :value="$ttAccount->currency->symbol.number_format($closingBalance, 2)" icon="currency" :color="$closingBalance < 0 ? 'amber' : 'indigo'" />
    </div>

    <div class="mt-6">
        <x-card title="Statement" :flush="true">
            @if ($entries->isEmpty() && $ttAccount->opening_balance === null)
                <x-empty-state icon="book-open" title="No entries yet"
                               description="Click 'Add Entry' to record the first received or paid amount." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">#</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Source</th>
                                <th class="px-4 py-3 text-right">Received</th>
                                <th class="px-4 py-3 text-right">Paid</th>
                                <th class="px-4 py-3 text-right">Balance</th>
                                <th class="px-4 py-3">Remarks</th>
                                @can('tt-accounts.edit')
                                    <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if ($ttAccount->opening_balance !== null)
                                <tr class="bg-gray-50/50">
                                    <td class="px-4 py-3 text-gray-400 sm:px-6">—</td>
                                    <td class="px-4 py-3 text-gray-600">—</td>
                                    <td class="px-4 py-3 font-medium text-gray-700" colspan="4">Opening balance</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        {{ number_format((float) $ttAccount->opening_balance, 2) }}
                                    </td>
                                    <td class="px-4 py-3"></td>
                                    @can('tt-accounts.edit')
                                        <td class="px-4 py-3"></td>
                                    @endcan
                                </tr>
                            @endif
                            @foreach ($entries as $entry)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ $entry->description }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        @if ($entry->source_amount !== null)
                                            {{ $entry->sourceCurrency?->symbol }}{{ number_format((float) $entry->source_amount, 2) }}
                                            @if ($entry->source_rate !== null)
                                                <span class="text-xs text-gray-400">@ {{ (float) $entry->source_rate }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-emerald-600">
                                        {{ $entry->type === EntryType::Received ? number_format((float) $entry->amount, 2) : '' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-rose-600">
                                        {{ $entry->type === EntryType::Paid ? number_format((float) $entry->amount, 2) : '' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium {{ $entry->running_balance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ number_format($entry->running_balance, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $entry->remarks ?? '' }}</td>
                                    @can('tt-accounts.edit')
                                        <td class="px-4 py-3 sm:px-6">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" x-data @click="$dispatch('open-modal', 'edit-entry-{{ $entry->id }}')"
                                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                    <x-icon name="pencil" class="size-4" />
                                                </button>
                                                <form method="POST" action="{{ route('admin.tt-accounts.entries.destroy', [$ttAccount, $entry]) }}"
                                                      onsubmit="return confirm('Delete this entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                        <x-icon name="trash" class="size-4" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 sm:px-6">Current Balance</td>
                                <td class="px-4 py-3 text-right text-base font-semibold {{ $closingBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $ttAccount->currency->symbol }}{{ number_format($closingBalance, 2) }}
                                </td>
                                <td></td>
                                @can('tt-accounts.edit')
                                    <td></td>
                                @endcan
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    @can('tt-accounts.edit')
        <x-modal name="add-entry" title="Add Entry" maxWidth="xl">
            @include('admin.tt-accounts._entry-form', [
                'action' => route('admin.tt-accounts.entries.store', $ttAccount),
                'entry' => null,
                'modalName' => 'add-entry',
            ])
        </x-modal>

        @foreach ($entries as $entry)
            <x-modal :name="'edit-entry-'.$entry->id" title="Edit Entry" maxWidth="xl">
                @include('admin.tt-accounts._entry-form', [
                    'action' => route('admin.tt-accounts.entries.update', [$ttAccount, $entry]),
                    'entry' => $entry,
                    'modalName' => 'edit-entry-'.$entry->id,
                ])
            </x-modal>
        @endforeach
    @endcan
</x-admin-layout>
