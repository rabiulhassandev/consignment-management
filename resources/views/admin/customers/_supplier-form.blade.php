@php
    /** @var \App\Models\Supplier|null $supplier */
    $supplier = $supplier ?? null;
    $prefix = $modalName;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($supplier)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalName }}">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="name" :id="$prefix.'-name'" label="Supplier name" :value="$supplier?->name" required />
        <x-form.select name="category_id" :id="$prefix.'-category'" label="Category" placeholder="Select a category" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $supplier?->category_id) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="contact_person" :id="$prefix.'-contact'" label="Contact person" :value="$supplier?->contact_person" />
        <x-form.input name="phone" :id="$prefix.'-phone'" label="Phone" :value="$supplier?->phone" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="wechat" :id="$prefix.'-wechat'" label="WeChat" :value="$supplier?->wechat" />
        <x-form.input name="address" :id="$prefix.'-address'" label="Address" :value="$supplier?->address" />
    </div>

    <x-form.textarea name="note" :id="$prefix.'-note'" label="Note" :value="$supplier?->note" rows="2" />

    <div class="flex items-center justify-end gap-3 pt-2">
        <x-button variant="secondary" @click="show = false">Cancel</x-button>
        <x-button type="submit">{{ $supplier ? 'Save Changes' : 'Add Supplier' }}</x-button>
    </div>
</form>
