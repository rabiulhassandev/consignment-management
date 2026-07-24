{{-- Branded full-page document frame: letterhead, brand graphics, and page footer around the document body. --}}
@props(['title' => null, 'backUrl' => null, 'heading', 'pdfUrl' => null])

@php
    $siteName = \App\Models\Setting::get('site_name', 'BNoor Group');
    $companyName = \App\Models\Setting::get('company_name') ?: $siteName;
    $tagline = \App\Models\Setting::get('company_tagline');
    $logo = \App\Models\Setting::get('site_logo');
    $website = \App\Models\Setting::get('company_website');
    $registrationNo = \App\Models\Setting::get('company_registration_no');
    $siteEmail = \App\Models\Setting::get('site_email');
    $chinaAddress = \App\Models\Setting::get('china_office_address');
    $chinaContact = \App\Models\Setting::get('china_office_contact');
    $dhakaAddress = \App\Models\Setting::get('dhaka_office_address');
    $dhakaContact = \App\Models\Setting::get('dhaka_office_contact');
@endphp

<x-print-layout :title="$title" :back-url="$backUrl" :pdf-url="$pdfUrl" :flush="true">
    {{-- Suppress the layout's default letterhead — this frame draws its own. --}}
    <x-slot:letterhead></x-slot:letterhead>

    <style>
        @media print {
            @page { size: A4; margin: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>

    <div class="relative flex flex-col" :class="letterhead ? 'min-h-[1086px] print:min-h-[296mm]' : 'pb-10'">
        {{-- Brand chevrons (left edge) --}}
        <div x-show="letterhead" class="pointer-events-none absolute left-0 top-[46%] z-0" aria-hidden="true">
            <svg width="66" height="200" viewBox="0 0 66 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polygon points="0,0 50,76 0,152" fill="#27aae1" />
                <polygon points="0,32 62,114 0,196" fill="#8dc63f" />
            </svg>
        </div>

        {{-- Brand rings (bottom-right corner) --}}
        <div x-show="letterhead" class="pointer-events-none absolute -bottom-14 -right-16 z-0" aria-hidden="true">
            <svg width="290" height="270" viewBox="0 0 290 270" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="bnoor-ring" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#8dc63f" />
                        <stop offset="1" stop-color="#27aae1" />
                    </linearGradient>
                </defs>
                <circle cx="205" cy="115" r="92" stroke="url(#bnoor-ring)" stroke-width="42" />
                <circle cx="98" cy="242" r="72" stroke="url(#bnoor-ring)" stroke-width="34" />
            </svg>
        </div>

        {{-- Letterhead --}}
        <div x-show="letterhead" class="relative z-10 px-10 pt-10">
            <div class="flex items-start justify-between gap-8">
                <div class="flex items-center gap-4">
                    @if ($logo)
                        <img src="{{ Storage::url($logo) }}" alt="{{ $companyName }}" class="h-14 w-auto">
                    @else
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-xl font-bold text-white">
                            {{ str($companyName)->substr(0, 1)->upper() }}
                        </span>
                    @endif
                    @if ($tagline)
                        <div class="h-12 w-px shrink-0 bg-gray-300"></div>
                        <p class="max-w-44 text-sm font-medium leading-snug tracking-[0.15em] text-gray-400">{{ $tagline }}</p>
                    @endif
                </div>
                <p class="whitespace-nowrap pt-3 text-3xl font-semibold uppercase tracking-[0.35em] text-slate-800">{{ $heading }}</p>
            </div>

            <p class="mt-3 text-2xl font-extrabold uppercase leading-none tracking-tight text-gray-950">{{ $companyName }}</p>
            @if ($registrationNo)
                <p class="mt-1.5 text-[11px] uppercase tracking-[0.15em] text-gray-400">Reg. No. {{ $registrationNo }}</p>
            @endif

            <div class="mt-4 border-t-2 border-slate-800"></div>
        </div>

        {{-- Document body --}}
        <div class="relative z-10 flex-1 px-10">
            {{ $slot }}
        </div>

        {{-- Page footer --}}
        <div x-show="letterhead" class="relative z-10 pt-12">
            <div class="space-y-1 px-10 pb-4 text-[11px] leading-relaxed text-gray-500">
                @if ($chinaAddress)
                    <p>
                        <span class="font-semibold uppercase tracking-[0.15em] text-gray-400">China Office:</span>
                        {{ $chinaAddress }}@if ($chinaContact) · {{ $chinaContact }}@endif
                    </p>
                @endif
                @if ($dhakaAddress)
                    <p>
                        <span class="font-semibold uppercase tracking-[0.15em] text-gray-400">Dhaka Office:</span>
                        {{ $dhakaAddress }}@if ($dhakaContact) · {{ $dhakaContact }}@endif
                    </p>
                @endif
                @if ($website || $siteEmail)
                    <p class="flex items-center gap-5 pt-0.5">
                        @if ($website)
                            <span class="flex items-center gap-1.5">
                                <svg class="size-3.5 text-[#27aae1]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                                {{ $website }}
                            </span>
                        @endif
                        @if ($siteEmail)
                            <span class="flex items-center gap-1.5">
                                <svg class="size-3.5 text-[#27aae1]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                {{ $siteEmail }}
                            </span>
                        @endif
                    </p>
                @endif
            </div>
            <div class="h-2.5 w-full bg-linear-to-r from-[#8dc63f] via-[#4db884] to-[#27aae1]"></div>
        </div>
    </div>
</x-print-layout>
