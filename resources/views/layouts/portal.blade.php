<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? "$title — " : '' }}{{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased">
    <header class="sticky top-0 z-20 border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-lg bg-indigo-600 text-base font-bold text-white">
                    {{ str($siteName)->substr(0, 1)->upper() }}
                </span>
                <span class="hidden truncate text-base font-semibold tracking-tight text-gray-900 sm:block">{{ $siteName }}</span>
            </a>

            <nav class="flex items-center gap-1">
                <x-nav-item :href="route('portal.dashboard')" icon="home" :active="request()->routeIs('portal.dashboard')">
                    Dashboard
                </x-nav-item>
                @if (Route::has('portal.consignments.index'))
                    <x-nav-item :href="route('portal.consignments.index')" icon="cube" :active="request()->routeIs('portal.consignments.*')">
                        My Consignments
                    </x-nav-item>
                @endif
            </nav>

            <div x-data="{ open: false }" class="relative ml-auto">
                <button type="button" @click="open = !open"
                        class="flex items-center gap-2 rounded-lg p-1.5 text-sm transition-colors hover:bg-gray-100">
                    <span class="flex size-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                        {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="hidden font-medium text-gray-700 sm:block">{{ auth()->user()->name }}</span>
                    <x-icon name="chevron-down" class="hidden size-4 text-gray-400 sm:block" />
                </button>

                <div x-cloak x-show="open" x-transition @click.outside="open = false"
                     class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                            <x-icon name="logout" class="size-4 text-gray-400" />
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl p-4 sm:p-6 lg:p-8">
        <x-flash />
        {{ $slot }}
    </main>
</body>
</html>
