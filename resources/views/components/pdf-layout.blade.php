{{-- Dompdf document frame styled to mirror the on-screen print letterhead as closely as dompdf allows. --}}
@props(['heading'])

@php
    $siteName = \App\Models\Setting::get('site_name', 'BNoor Group');
    $companyName = \App\Models\Setting::get('company_name') ?: $siteName;
    $tagline = \App\Models\Setting::get('company_tagline');
    $logo = \App\Models\Setting::get('site_logo');
    $website = \App\Models\Setting::get('company_website');
    $siteEmail = \App\Models\Setting::get('site_email');
    $chinaAddress = \App\Models\Setting::get('china_office_address');
    $chinaContact = \App\Models\Setting::get('china_office_contact');
    $dhakaAddress = \App\Models\Setting::get('dhaka_office_address');
    $dhakaContact = \App\Models\Setting::get('dhaka_office_contact');
    $hasFooter = $chinaAddress || $dhakaAddress || $website || $siteEmail;

    // Embed the logo as a data URI (enable_remote is off, so Storage::url() would not load).
    $logoUri = null;
    if ($logo) {
        $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($logo);
        if (is_file($logoPath)) {
            $logoUri = 'data:image/'.pathinfo($logoPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($logoPath));
        }
    }

    // Subsetted CJK font so the Chinese office address renders instead of tofu boxes.
    $cjkFont = str_replace('\\', '/', resource_path('fonts/cjk-subset.ttf'));
    $hasCjkFont = is_file($cjkFont);

    // Brand chevrons (left edge) and rings (bottom-right) as data-URI SVGs.
    // php-svg-lib renders solid fills/strokes only (no gradients), so the rings use solid brand strokes.
    $chevronsSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="66" height="200" viewBox="0 0 66 200" fill="none">'
        .'<polygon points="0,0 50,76 0,152" fill="#27aae1"/><polygon points="0,32 62,114 0,196" fill="#8dc63f"/></svg>';
    $ringsSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="290" height="270" viewBox="0 0 290 270" fill="none">'
        .'<circle cx="205" cy="115" r="92" fill="none" stroke="#27aae1" stroke-width="42"/>'
        .'<circle cx="98" cy="242" r="72" fill="none" stroke="#8dc63f" stroke-width="34"/></svg>';
    $chevronsUri = 'data:image/svg+xml;base64,'.base64_encode($chevronsSvg);
    $ringsUri = 'data:image/svg+xml;base64,'.base64_encode($ringsSvg);

    // Break a long tagline onto a second line for the compact letterhead column.
    $taglineHtml = $tagline ? nl2br(e(wordwrap($tagline, 18, "\n", true))) : null;
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 40px 168px 40px; }
        @if ($hasCjkFont)
            @font-face { font-family: 'cjk'; font-style: normal; font-weight: normal; src: url('{{ $cjkFont }}') format('truetype'); }
        @endif
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1e293b; font-size: 12px; line-height: 1.5; }
        h1, h2, h3, p, table { margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        .muted { color: #94a3b8; }
        .strong { font-weight: bold; color: #0f172a; }
        .dark { color: #1e293b; }
        .uppercase { text-transform: uppercase; letter-spacing: 1px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .num { font-variant-numeric: tabular-nums; }
        .cjk { font-family: 'cjk', DejaVu Sans, sans-serif; }

        .letterhead-row { padding-bottom: 12px; }
        .company { font-size: 19px; font-weight: bold; text-transform: uppercase; color: #0a0a0a; letter-spacing: 0.3px; }
        .heading { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 8px; color: #1e293b; }
        .tagline { font-size: 13px; color: #94a3b8; line-height: 1.3; }

        .chevrons { position: fixed; left: -40px; top: 430px; z-index: -2; }
        .rings { position: fixed; right: -46px; bottom: -140px; z-index: -1; }

        .footer { position: fixed; bottom: -146px; left: 0; right: 0; }
        .footer .info { font-size: 9px; color: #64748b; line-height: 1.6; padding-bottom: 5px; }
        .footer .info .label { font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        .brandbar { display: block; width: 100%; height: 9px; background-color: #4db884; background-image: linear-gradient(to right, #8dc63f 0%, #4db884 50%, #27aae1 100%); z-index: -1; }
    </style>
</head>
<body>
    {{-- Brand decorations (drawn on every page, like the print frame) --}}
    <img class="chevrons" src="{{ $chevronsUri }}" width="66" height="200" alt="">
    <img class="rings" src="{{ $ringsUri }}" width="200" height="186" alt="">

    {{-- Letterhead --}}
    <table class="letterhead-row">
        <tr>
            <td style="vertical-align: middle; width: 62%;">
                <table>
                    <tr>
                        @if ($logoUri)
                            <td style="vertical-align: middle; width: 210px;">
                                <img src="{{ $logoUri }}" style="width: 205px; height: auto;" alt="{{ $companyName }}">
                            </td>
                        @endif
                        @if ($taglineHtml)
                            <td style="vertical-align: middle; padding-left: 14px; border-left: 1px solid #d1d5db;">
                                <span class="tagline">{!! $taglineHtml !!}</span>
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
            <td class="right" style="vertical-align: middle;">
                <span class="heading">{{ $heading }}</span>
            </td>
        </tr>
    </table>
    <p style="padding-top: 0px;"><span class="company">{{ $companyName }}</span></p>
    <div style="border-bottom: 2px solid #1e293b; margin-top: 6px;"></div>

    {{-- Document body --}}
    <div style="padding-top: 4px;">
        {{ $slot }}
    </div>

    {{-- Page footer --}}
    <div class="footer">
        @if ($hasFooter)
            <div class="info">
                @if ($chinaAddress)
                    <div><span class="label">China Office:</span> <span class="cjk">{{ $chinaAddress }}</span>@if ($chinaContact) <span class="cjk"> · {{ $chinaContact }}</span>@endif</div>
                @endif
                @if ($dhakaAddress)
                    <div><span class="label">Dhaka Office:</span> {{ $dhakaAddress }}@if ($dhakaContact) · {{ $dhakaContact }}@endif</div>
                @endif
                @if ($website || $siteEmail)
                    <div style="padding-top: 1px;">
                        @if ($website)<span>{{ $website }}</span>@endif
                        @if ($website && $siteEmail) &nbsp;&nbsp;&nbsp; @endif
                        @if ($siteEmail)<span>{{ $siteEmail }}</span>@endif
                    </div>
                @endif
            </div>
        @endif
        <div class="brandbar"></div>
    </div>
</body>
</html>
