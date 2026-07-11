@props(['title' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
            <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['p-4 sm:p-6' => ! $flush])>
        {{ $slot }}
    </div>
</div>
