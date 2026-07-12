<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title — " : '' }}{{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    {{-- Decorative background --}}
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 left-1/2 size-144 -translate-x-1/2 rounded-full bg-indigo-200/40 blur-3xl"></div>
        <div class="absolute -bottom-48 right-[15%] size-112 rounded-full bg-violet-200/40 blur-3xl"></div>
    </div>

    <div class="relative flex min-h-screen flex-col items-center justify-center gap-8 px-4 py-10">
        <a href="{{ url('/') }}" class="flex flex-col items-center gap-3">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-linear-to-br from-indigo-500 to-violet-600 text-2xl font-bold text-white shadow-lg shadow-indigo-600/25">
                {{ str($siteName)->substr(0, 1)->upper() }}
            </span>
            <span class="text-xl font-semibold tracking-tight text-gray-900">{{ $siteName }}</span>
        </a>

        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl shadow-gray-900/5 ring-1 ring-gray-950/5 sm:p-8">
            {{ $slot }}
        </div>

        <p class="text-xs text-gray-400">&copy; {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
    </div>
</body>
</html>
