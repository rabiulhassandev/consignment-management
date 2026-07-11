<x-admin-layout title="Categories">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Categories</h1>
            <p class="mt-1 text-sm text-gray-500">Product categories used for suppliers and purchase items.</p>
        </div>
        <x-button icon="plus" x-data @click="$dispatch('open-modal', 'add-category')">New Category</x-button>
    </div>

    <x-card :flush="true" class="max-w-3xl">
        @if ($categories->isEmpty())
            <x-empty-state icon="tag" title="No categories yet"
                           description="Add categories like Cotton, Polyester or Denim." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Name</th>
                            <th class="px-4 py-3">Suppliers</th>
                            <th class="px-4 py-3">Purchase Items</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($categories as $category)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $category->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $category->suppliers_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $category->purchase_items_count }}</td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" x-data @click="$dispatch('open-modal', 'edit-category-{{ $category->id }}')"
                                                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                            <x-icon name="pencil" class="size-4" />
                                        </button>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
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
            @if ($categories->hasPages())
                <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                    {{ $categories->links() }}
                </div>
            @endif
        @endif
    </x-card>

    <x-modal name="add-category" title="New Category" max-width="md">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_modal" value="add-category">
            <x-form.input name="name" id="add-category-name" label="Category name" required />
            <div class="flex items-center justify-end gap-3 pt-2">
                <x-button variant="secondary" @click="show = false">Cancel</x-button>
                <x-button type="submit">Create</x-button>
            </div>
        </form>
    </x-modal>

    @foreach ($categories as $category)
        <x-modal name="edit-category-{{ $category->id }}" title="Edit Category" max-width="md">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="edit-category-{{ $category->id }}">
                <x-form.input name="name" id="edit-category-{{ $category->id }}-name" label="Category name" :value="$category->name" required />
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button type="submit">Save</x-button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-admin-layout>
