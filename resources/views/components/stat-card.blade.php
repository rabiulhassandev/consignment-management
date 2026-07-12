@props(['label', 'value', 'icon' => 'cube', 'color' => 'indigo'])

@php
    $iconClasses = match ($color) {
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'rose' => 'bg-rose-50 text-rose-600',
        default => 'bg-indigo-50 text-indigo-600',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl border border-gray-200/75 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm']) }}>
    <div class="flex size-11 shrink-0 items-center justify-center rounded-lg {{ $iconClasses }}">
        <x-icon :name="$icon" class="size-6" />
    </div>
    <div class="min-w-0">
        <p class="truncate text-sm text-gray-500">{{ $label }}</p>
        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $value }}</p>
    </div>
</div>
