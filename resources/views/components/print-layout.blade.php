@props(['title' => null, 'backUrl' => null])

@php
    $siteName = \App\Models\Setting::get('site_name', 'BNoor Group');
    $siteLogo = \App\Models\Setting::get('site_logo');
    $siteAddress = \App\Models\Setting::get('site_address');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title — " : '' }}{{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page { size: A4; margin: 14mm; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased print:bg-white">
<div x-data="{ letterhead: true }">
    {{-- Toolbar (hidden when printing) --}}
    <div class="sticky top-0 z-10 border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur-md print:hidden">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3">
            @if ($backUrl)
                <x-button variant="ghost" :href="$backUrl">&larr; Back</x-button>
            @else
                <span></span>
            @endif

            <div class="flex items-center gap-4">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" x-model="letterhead"
                           class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                    Company header
                    <span class="hidden text-xs text-gray-400 sm:inline">(uncheck for pre-printed pad)</span>
                </label>
                <x-button icon="printer" onclick="window.print()">Print</x-button>
            </div>
        </div>
    </div>

    {{-- Document sheet --}}
    <div class="mx-auto my-6 max-w-3xl bg-white p-10 shadow-sm print:my-0 print:max-w-none print:p-0 print:shadow-none">
        <div x-show="letterhead">
            @isset($letterhead)
                {{ $letterhead }}
            @else
                <div class="mb-6 border-b-2 border-gray-900 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            @if ($siteLogo)
                                <img src="{{ Storage::url($siteLogo) }}" alt="{{ $siteName }}" class="h-12 w-auto">
                            @endif
                            <div>
                                <p class="text-xl font-bold uppercase tracking-tight">{{ $siteName }}</p>
                                @if ($siteAddress)
                                    <p class="text-xs text-gray-600">{{ $siteAddress }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right text-xs text-gray-600">
                            @if ($sitePhone)
                                <p>{{ $sitePhone }}</p>
                            @endif
                            @if ($siteEmail)
                                <p>{{ $siteEmail }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endisset
        </div>

        {{-- Blank space reserved for the pre-printed letterhead pad --}}
        <div x-show="!letterhead" class="h-36" aria-hidden="true"></div>

        {{ $slot }}
    </div>
</div>
</body>
</html>
