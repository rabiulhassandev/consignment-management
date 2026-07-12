@props(['href', 'icon', 'active' => false, 'variant' => 'light'])

@if ($variant === 'dark')
    <a href="{{ $href }}"
       {{ $attributes->class([
            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
            'bg-white/10 text-white' => $active,
            'text-gray-400 hover:bg-white/5 hover:text-white' => ! $active,
       ]) }}>
        <x-icon :name="$icon" @class(['text-indigo-400' => $active, 'text-gray-500 transition-colors group-hover:text-gray-300' => ! $active]) />
        {{ $slot }}
    </a>
@else
    <a href="{{ $href }}"
       {{ $attributes->class([
            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
            'bg-indigo-50 text-indigo-700' => $active,
            'text-gray-600 hover:bg-gray-100 hover:text-gray-900' => ! $active,
       ]) }}>
        <x-icon :name="$icon" @class(['text-indigo-600' => $active, 'text-gray-400 transition-colors group-hover:text-gray-500' => ! $active]) />
        {{ $slot }}
    </a>
@endif
