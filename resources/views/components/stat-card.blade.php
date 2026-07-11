@props(['label', 'value', 'icon' => 'cube'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex size-11 items-center justify-center rounded-lg bg-indigo-50">
        <x-icon :name="$icon" class="size-6 text-indigo-600" />
    </div>
    <div class="min-w-0">
        <p class="truncate text-sm text-gray-500">{{ $label }}</p>
        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $value }}</p>
    </div>
</div>
