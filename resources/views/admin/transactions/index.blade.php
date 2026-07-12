@php
    use App\Enums\TransactionType;
@endphp

<x-admin-layout title="Transactions">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Transactions</h1>
            <p class="mt-1 text-sm text-gray-500">Company income and expense entries.</p>
        </div>
        @can('transactions.create')
            <div class="flex items-center gap-2">
                <x-button icon="arrow-trending-up" x-data @click="$dispatch('open-modal', 'add-income')">Add Income</x-button>
                <x-button variant="danger" icon="arrow-trending-down" x-data @click="$dispatch('open-modal', 'add-expense')">Add Expense</x-button>
            </div>
        @endcan
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.transactions.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <select name="type"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All types</option>
                @foreach (TransactionType::cases() as $transactionType)
                    <option value="{{ $transactionType->value }}" @selected($type === $transactionType->value)>{{ $transactionType->label() }}</option>
                @endforeach
            </select>
            <select name="category"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All categories</option>
                <optgroup label="Income">
                    @foreach ($incomeCategories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Expense">
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </optgroup>
            </select>
            <input type="date" name="from" value="{{ $from }}" title="From date"
                   class="rounded-lg border-0 py-2 px-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <span class="text-sm text-gray-400">to</span>
            <input type="date" name="to" value="{{ $to }}" title="To date"
                   class="rounded-lg border-0 py-2 px-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search description…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if ($type !== '' || $categoryId > 0 || $from !== '' || $to !== '' || $search !== '')
                <x-button variant="ghost" :href="route('admin.transactions.index')">Reset</x-button>
            @endif
        </form>

        @if ($transactions->isEmpty())
            <x-empty-state icon="banknotes" title="No transactions found"
                           description="Use 'Add Income' or 'Add Expense' to record your first entry." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/75">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 sm:px-6">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$transaction->type === TransactionType::Income ? 'green' : 'red'">
                                        {{ $transaction->type->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $transaction->category->name }}</td>
                                <td class="max-w-xs truncate px-4 py-3 text-gray-600">{{ $transaction->description ?? '—' }}</td>
                                <td @class([
                                    'whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums',
                                    'text-emerald-600' => $transaction->type === TransactionType::Income,
                                    'text-rose-600' => $transaction->type === TransactionType::Expense,
                                ])>
                                    {{ number_format((float) $transaction->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('transactions.edit')
                                            <button type="button" x-data @click="$dispatch('open-modal', 'edit-transaction-{{ $transaction->id }}')"
                                                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </button>
                                        @endcan
                                        @can('transactions.delete')
                                            <form method="POST" action="{{ route('admin.transactions.destroy', $transaction) }}"
                                                  onsubmit="return confirm('Delete this entry?')">
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
            @if ($transactions->hasPages())
                <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                    {{ $transactions->links() }}
                </div>
            @endif
        @endif
    </x-card>

    @can('transactions.create')
        <x-modal name="add-income" title="Add Income" max-width="md">
            <form method="POST" action="{{ route('admin.transactions.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="add-income">
                <input type="hidden" name="type" value="{{ TransactionType::Income->value }}">
                <x-form.input name="transaction_date" id="add-income-date" type="date" label="Date"
                              :value="now()->toDateString()" required />
                <x-form.select name="transaction_category_id" id="add-income-category" label="Category"
                               placeholder="Select category" required>
                    @foreach ($incomeCategories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('transaction_category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input name="amount" id="add-income-amount" type="number" label="Amount"
                              step="0.01" min="0.01" placeholder="0.00" required />
                <x-form.textarea name="description" id="add-income-description" label="Description" rows="2" />
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button type="submit">Add Income</x-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="add-expense" title="Add Expense" max-width="md">
            <form method="POST" action="{{ route('admin.transactions.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="add-expense">
                <input type="hidden" name="type" value="{{ TransactionType::Expense->value }}">
                <x-form.input name="transaction_date" id="add-expense-date" type="date" label="Date"
                              :value="now()->toDateString()" required />
                <x-form.select name="transaction_category_id" id="add-expense-category" label="Category"
                               placeholder="Select category" required>
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('transaction_category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input name="amount" id="add-expense-amount" type="number" label="Amount"
                              step="0.01" min="0.01" placeholder="0.00" required />
                <x-form.textarea name="description" id="add-expense-description" label="Description" rows="2" />
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button variant="danger" type="submit">Add Expense</x-button>
                </div>
            </form>
        </x-modal>
    @endcan

    @can('transactions.edit')
        @foreach ($transactions as $transaction)
            <x-modal name="edit-transaction-{{ $transaction->id }}" title="Edit {{ $transaction->type->label() }} Entry" max-width="md">
                <form method="POST" action="{{ route('admin.transactions.update', $transaction) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="edit-transaction-{{ $transaction->id }}">
                    <input type="hidden" name="type" value="{{ $transaction->type->value }}">
                    <x-form.input name="transaction_date" id="edit-transaction-{{ $transaction->id }}-date" type="date" label="Date"
                                  :value="$transaction->transaction_date->toDateString()" required />
                    <x-form.select name="transaction_category_id" id="edit-transaction-{{ $transaction->id }}-category" label="Category"
                                   placeholder="Select category" required>
                        @foreach ($transaction->type === TransactionType::Income ? $incomeCategories : $expenseCategories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('transaction_category_id', $transaction->transaction_category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.input name="amount" id="edit-transaction-{{ $transaction->id }}-amount" type="number" label="Amount"
                                  step="0.01" min="0.01" :value="$transaction->amount" required />
                    <x-form.textarea name="description" id="edit-transaction-{{ $transaction->id }}-description" label="Description" rows="2"
                                     :value="$transaction->description" />
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-button variant="secondary" @click="show = false">Cancel</x-button>
                        <x-button type="submit">Save</x-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endcan
</x-admin-layout>
