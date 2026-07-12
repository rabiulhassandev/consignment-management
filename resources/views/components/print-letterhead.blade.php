@props(['heading'])

@php
    $siteName = \App\Models\Setting::get('site_name', 'BNoor Group');
    $companyName = \App\Models\Setting::get('company_name') ?: $siteName;
    $tagline = \App\Models\Setting::get('company_tagline');
    $logo = \App\Models\Setting::get('site_logo');
    $chinaAddress = \App\Models\Setting::get('china_office_address');
    $chinaContact = \App\Models\Setting::get('china_office_contact');
    $dhakaAddress = \App\Models\Setting::get('dhaka_office_address');
    $dhakaContact = \App\Models\Setting::get('dhaka_office_contact');
    $siteAddress = \App\Models\Setting::get('site_address');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
@endphp

<div class="mb-10">
    <div class="flex items-start justify-between gap-8">
        <div class="">
            @if ($logo)
                <img src="{{ Storage::url($logo) }}" alt="{{ $companyName }}" class="h-12 w-auto">
            @else
                <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-lg font-bold text-white">
                    {{ str($companyName)->substr(0, 1)->upper() }}
                </span>
            @endif
            <div>
                <p class="text-lg font-semibold tracking-tight text-gray-900">{{ $companyName }}</p>
                @if ($tagline)
                    <p class="mt-0.5 text-[11px] uppercase tracking-[0.2em] text-gray-500">{{ $tagline }}</p>
                @endif
            </div>
        </div>
        <p class="whitespace-nowrap pt-1 text-3xl font-light uppercase tracking-[0.3em] text-gray-900">{{ $heading }}</p>
    </div>

    <div class="mt-6 border-t border-gray-900"></div>

    @if ($chinaAddress || $dhakaAddress)
        <div class="mt-3 flex justify-between gap-10 text-[11px] leading-relaxed text-gray-500">
            @if ($chinaAddress)
                <div class="max-w-[48%]">
                    <p class="font-semibold uppercase tracking-widest text-gray-400">China Office</p>
                    <p class="mt-1">{{ $chinaAddress }}</p>
                    @if ($chinaContact)
                        <p>{{ $chinaContact }}</p>
                    @endif
                </div>
            @endif
            {{-- <div>
                <img src="{{ Storage::url($logo) }}" alt="{{ $companyName }}" class="h-12 w-auto">
            </div> --}}
            @if ($dhakaAddress)
                <div class="max-w-[48%] text-right">
                    <p class="font-semibold uppercase tracking-widest text-gray-400">Dhaka Office</p>
                    <p class="mt-1">{{ $dhakaAddress }}</p>
                    @if ($dhakaContact)
                        <p>{{ $dhakaContact }}</p>
                    @endif
                </div>
            @endif
        </div>
    @elseif ($siteAddress || $sitePhone || $siteEmail)
        <p class="mt-3 text-center text-[11px] text-gray-500">
            {{ collect([$siteAddress, $sitePhone, $siteEmail])->filter()->implode(' · ') }}
        </p>
    @endif
</div>
