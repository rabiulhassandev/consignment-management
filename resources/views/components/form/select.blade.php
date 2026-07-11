@props(['label' => null, 'name', 'value' => null, 'required' => false, 'placeholder' => null, 'id' => null])

@php $id = $id ?? $name; @endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700">
            {{ $label }}
            @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->except('class')->merge([
            'class' => 'block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 ' . ($errors->has($name) ? 'ring-red-300 focus:ring-red-500' : ''),
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
