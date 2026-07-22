@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $footerNote = \App\Models\Setting::get('invoice_footer_note');
    $currency = $salesContract->currency;
    $symbol = $currency->symbol;
    $blankRows = max(0, 4 - $salesContract->items->count());
@endphp

<x-print-page :title="'Sales Contract '.$salesContract->contract_no"
              :back-url="route('admin.sales-contracts.show', $salesContract)"
              heading="Contract">
    {{-- Buyer + contract meta --}}
    <table class="mt-8 w-full border border-slate-800 text-[13px]">
        <tbody>
            <tr>
                <th class="w-24 border border-slate-800 px-3 py-2 text-left align-top font-bold text-slate-800">Buyer</th>
                <td class="border border-slate-800 px-3 py-2 align-top text-slate-900">{{ $salesContract->buyer }}</td>
                <th class="w-28 border border-slate-800 px-3 py-2 text-left align-top font-bold text-slate-800">Date</th>
                <td class="w-40 border border-slate-800 px-3 py-2 align-top font-bold uppercase text-slate-900">
                    {{ $salesContract->contract_date->format('d F Y') }}
                </td>
            </tr>
            <tr>
                <th class="border border-slate-800 px-3 py-2 text-left align-top font-bold text-slate-800">Address</th>
                <td class="border border-slate-800 px-3 py-2 align-top text-slate-700">{{ $salesContract->buyer_address ?: '—' }}</td>
                <th class="border border-slate-800 px-3 py-2 text-left align-top font-bold text-slate-800">Contract No.</th>
                <td class="border border-slate-800 px-3 py-2 align-top font-bold text-slate-900">{{ $salesContract->contract_no }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Items --}}
    <table class="mt-6 w-full border border-slate-800 text-[13px]">
        <thead>
            <tr class="bg-gray-50 text-center text-[11px] font-bold uppercase leading-tight text-slate-800">
                <th class="w-12 border border-slate-800 px-2 py-2">Sl. No.</th>
                <th class="border border-slate-800 px-2 py-2">Description</th>
                <th class="w-24 border border-slate-800 px-2 py-2">H.S. Code</th>
                <th class="w-20 border border-slate-800 px-2 py-2">Quantity</th>
                <th class="w-20 border border-slate-800 px-2 py-2">Unit</th>
                <th class="w-24 border border-slate-800 px-2 py-2">Unit / {{ $currency->code }}</th>
                <th class="w-28 border border-slate-800 px-2 py-2">Total / {{ $currency->code }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salesContract->items as $item)
                <tr>
                    <td class="border border-slate-800 px-2 py-2 text-center text-gray-500">{{ $loop->iteration }}</td>
                    <td class="border border-slate-800 px-3 py-2 text-slate-900">{{ $item->description }}</td>
                    <td class="border border-slate-800 px-2 py-2 text-center tabular-nums text-slate-700">{{ $item->hs_code }}</td>
                    <td class="border border-slate-800 px-2 py-2 text-center tabular-nums text-slate-700">
                        {{ $item->quantity !== null ? rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') : '' }}
                    </td>
                    <td class="border border-slate-800 px-2 py-2 text-center uppercase text-slate-700">{{ $item->unit }}</td>
                    <td class="border border-slate-800 px-2 py-2 text-right tabular-nums text-slate-700">
                        {{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2) : '' }}
                    </td>
                    <td class="border border-slate-800 px-2 py-2 text-right font-medium tabular-nums text-slate-900">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach

            {{-- Ruled blank rows so short contracts keep the printed grid --}}
            @for ($i = 0; $i < $blankRows; $i++)
                <tr>
                    @for ($column = 0; $column < 7; $column++)
                        <td class="border border-slate-800 px-2 py-2">&nbsp;</td>
                    @endfor
                </tr>
            @endfor

            @if ($salesContract->freight_charge !== null)
                <tr>
                    <td class="border border-slate-800 px-2 py-2"></td>
                    <td class="border border-slate-800 px-3 py-2 font-medium uppercase text-slate-800">Freight Charge</td>
                    <td class="border border-slate-800 px-2 py-2"></td>
                    <td class="border border-slate-800 px-2 py-2"></td>
                    <td class="border border-slate-800 px-2 py-2"></td>
                    <td class="border border-slate-800 px-2 py-2 text-right tabular-nums text-slate-700">
                        {{ number_format((float) $salesContract->freight_charge, 2) }}
                    </td>
                    <td class="border border-slate-800 px-2 py-2 text-right font-medium tabular-nums text-slate-900">
                        {{ number_format((float) $salesContract->freight_charge, 2) }}
                    </td>
                </tr>
            @endif

            <tr class="bg-gray-50">
                <td class="border border-slate-800 px-2 py-2"></td>
                <td colspan="5" class="border border-slate-800 px-3 py-2 font-bold uppercase tracking-wide text-slate-800">Total Amount</td>
                <td class="border border-slate-800 px-2 py-2 text-right text-[15px] font-bold tabular-nums text-slate-900">
                    {{ $symbol }}{{ number_format($totalAmount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Amount in words --}}
    <div class="mt-5 flex items-baseline gap-4 text-[13px]">
        <span class="shrink-0 font-bold text-slate-800">In Words</span>
        <span class="font-medium text-slate-900">{{ $amountInWords }}</span>
    </div>

    {{-- Terms & conditions --}}
    @if ($termLines->isNotEmpty())
        <div class="mt-6 text-[13px] leading-relaxed">
            <p class="font-bold uppercase tracking-wide text-slate-800">Terms and Condition:</p>
            <ol class="mt-1.5 list-decimal space-y-1 pl-8 text-slate-700">
                @foreach ($termLines as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- Confirmation blocks --}}
    <table class="mt-10 w-full border border-slate-800 text-[13px]">
        <thead>
            <tr class="text-center text-[15px] font-bold text-slate-800">
                <th class="w-1/2 border border-slate-800 px-3 py-2.5">Seller Confirmation</th>
                <th class="w-1/2 border border-slate-800 px-3 py-2.5">Buyer Confirmation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="h-28 border border-slate-800 px-3 py-2 align-bottom text-center text-xs text-gray-400">
                    For {{ $companyName }}
                </td>
                <td class="h-28 border border-slate-800 px-3 py-2 align-bottom text-center text-xs text-gray-400">
                    For {{ $salesContract->buyer }}
                </td>
            </tr>
        </tbody>
    </table>

    @if ($footerNote)
        <p x-show="letterhead" class="mt-8 text-center text-[13px] italic text-gray-400">
            {{ $footerNote }}
        </p>
    @endif
</x-print-page>
