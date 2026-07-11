@props(['color' => 'gray'])

@php
    $classes = match ($color) {
        'green' => 'bg-green-50 text-green-700 ring-green-600/20',
        'yellow' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        default => 'bg-gray-50 text-gray-600 ring-gray-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
