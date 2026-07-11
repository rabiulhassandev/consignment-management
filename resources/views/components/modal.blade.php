@props(['name', 'title' => null, 'maxWidth' => 'lg'])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '4xl' => 'sm:max-w-4xl',
        default => 'sm:max-w-lg',
    };
@endphp

<div
    x-data="{ show: @js(old('_modal') === $name) }"
    x-on:open-modal.window="show = ($event.detail === '{{ $name }}')"
    x-on:close-modal.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-900/50" @click="show = false"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition
            class="relative w-full {{ $maxWidthClass }} rounded-xl bg-white p-6 shadow-xl"
            @click.stop
        >
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <button type="button" @click="show = false" class="text-gray-400 transition-colors hover:text-gray-600">
                    <x-icon name="x-mark" />
                </button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
