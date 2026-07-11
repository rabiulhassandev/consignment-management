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
<body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 px-4 py-10">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <span class="flex size-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-bold text-white">
                {{ str($siteName)->substr(0, 1)->upper() }}
            </span>
            <span class="text-xl font-semibold tracking-tight text-gray-900">{{ $siteName }}</span>
        </a>

        <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
