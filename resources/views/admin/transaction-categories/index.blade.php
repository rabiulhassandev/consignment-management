@php
    use App\Enums\TransactionType;

    $groups = [
        [
            'type' => TransactionType::Income,
            'title' => 'Income Categories',
            'description' => 'Used only for income entries.',
            'categories' => $incomeCategories,
        ],
        [
            'type' => TransactionType::Expense,
            'title' => 'Expense Categories',
            'description' => 'Used only for expense entries.',
            'categories' => $expenseCategories,
        ],
    ];
@endphp

<x-admin-layout title="Transaction Categories">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Transaction Categories</h1>
        <p class="mt-1 text-sm text-gray-500">Separate category lists for income and expense entries.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach ($groups as $group)
            <x-card :flush="true" :title="$group['title']">
                <x-slot:actions>
                    <x-button icon="plus" x-data @click="$dispatch('open-modal', 'add-{{ $group['type']->value }}-category')">New</x-button>
                </x-slot:actions>

                @if ($group['categories']->isEmpty())
                    <x-empty-state icon="tag" title="No {{ $group['type']->value }} categories yet"
                                   description="{{ $group['description'] }}" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50/75">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 sm:px-6">Name</th>
                                    <th class="px-4 py-3">Entries</th>
                                    <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($group['categories'] as $category)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $category->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $category->transactions_count }}</td>
                                        <td class="px-4 py-3 sm:px-6">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" x-data @click="$dispatch('open-modal', 'edit-transaction-category-{{ $category->id }}')"
                                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                    <x-icon name="pencil" class="size-4" />
                                                </button>
                                                <form method="POST" action="{{ route('admin.transaction-categories.destroy', $category) }}"
                                                      onsubmit="return confirm('Delete this category?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                        <x-icon name="trash" class="size-4" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>

    @foreach ($groups as $group)
        <x-modal name="add-{{ $group['type']->value }}-category" title="New {{ $group['type']->label() }} Category" max-width="md">
            <form method="POST" action="{{ route('admin.transaction-categories.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="add-{{ $group['type']->value }}-category">
                <input type="hidden" name="type" value="{{ $group['type']->value }}">
                <x-form.input name="name" id="add-{{ $group['type']->value }}-category-name" label="Category name" required />
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button type="submit">Create</x-button>
                </div>
            </form>
        </x-modal>

        @foreach ($group['categories'] as $category)
            <x-modal name="edit-transaction-category-{{ $category->id }}" title="Edit {{ $group['type']->label() }} Category" max-width="md">
                <form method="POST" action="{{ route('admin.transaction-categories.update', $category) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="edit-transaction-category-{{ $category->id }}">
                    <x-form.input name="name" id="edit-transaction-category-{{ $category->id }}-name" label="Category name" :value="$category->name" required />
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-button variant="secondary" @click="show = false">Cancel</x-button>
                        <x-button type="submit">Save</x-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endforeach
</x-admin-layout>
