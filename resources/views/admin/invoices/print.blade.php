@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $bankRows = $invoice->printedPaymentDetails();
    $paymentTerms = \App\Models\Setting::get('invoice_payment_terms');
    $terms = \App\Models\Setting::get('invoice_terms');
    $signatoryName = \App\Models\Setting::get('invoice_signatory_name');
    $signatoryDesignation = \App\Models\Setting::get('invoice_signatory_designation');
    $footerNote = \App\Models\Setting::get('invoice_footer_note');
    $billToAddress = $invoice->billToAddress();
@endphp

<x-print-page :title="'Invoice '.$invoice->invoice_no" :back-url="route('admin.invoices.show', $invoice)" :pdf-url="route('admin.invoices.pdf', $invoice)" heading="Invoice" :watermark="true">
    {{-- Billed to + invoice meta --}}
    <div class="mt-9 flex items-start justify-between gap-8">
        <div class="flex items-start gap-4">
            <p class="pt-1 text-[13px] font-semibold uppercase tracking-[0.2em] text-gray-400">Billed To</p>
            <div>
                <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $invoice->billToName() }}</p>
                @if ($billToAddress)
                    <p class="mt-1 max-w-xs whitespace-pre-line text-[13px] leading-relaxed text-gray-500">{{ $billToAddress }}</p>
                @endif
            </div>
        </div>
        <dl class="shrink-0 space-y-1.5 text-[15px]">
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Invoice No</dt>
                <dd class="w-28 text-right font-bold tabular-nums text-slate-800">{{ $invoice->invoice_no }}</dd>
            </div>
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Issue Date</dt>
                <dd class="w-28 text-right font-bold text-slate-800">{{ $invoice->invoice_date->format('d M Y') }}</dd>
            </div>
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Currency</dt>
                <dd class="w-28 text-right font-bold text-slate-800">{{ $invoice->currency->code }}</dd>
            </div>
        </dl>
    </div>

    {{-- Items --}}
    <table class="mt-12 w-full text-[15px]">
        <thead>
            <tr class="border-b-[3px] border-slate-800 text-xs uppercase tracking-[0.2em] text-gray-400">
                <th class="pb-3 text-left font-semibold">Description</th>
                <th class="pb-3 text-right font-semibold">Qty / Weight</th>
                <th class="pb-3 text-right font-semibold">Rate</th>
                <th class="pb-3 pl-8 text-right font-semibold">Amount ({{ $invoice->currency->code }})</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="py-4 pr-4 text-slate-900">{{ $item->description }}</td>
                    <td class="py-4 text-right tabular-nums text-gray-500">
                        {{ $item->quantity !== null ? number_format((float) $item->quantity, 2) : '—' }}
                    </td>
                    <td class="py-4 text-right tabular-nums text-gray-500">
                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '—' }}
                    </td>
                    <td class="py-4 pl-8 text-right font-bold tabular-nums text-slate-900">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Total --}}
    <div class="mt-8 flex justify-end">
        <div class="w-88">
            <div class="flex items-baseline justify-between border-t-2 border-slate-800 pt-4">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-400">Total</span>
                <span class="text-3xl font-bold tabular-nums text-slate-900">
                    {{ $invoice->currency->symbol }}{{ number_format((float) $totalAmount, 2) }}
                </span>
            </div>
            @if ($paymentTerms)
                <p class="mt-2 text-right text-xs font-medium text-slate-600">{{ $paymentTerms }}</p>
            @endif
        </div>
    </div>

    {{-- Amount in words --}}
    <div class="mt-5 flex items-baseline gap-4 text-[13px]">
        <span class="shrink-0 font-bold uppercase tracking-[0.15em] text-gray-400">In Words</span>
        <span class="font-medium text-slate-900">{{ $amountInWords }}</span>
    </div>

    {{-- Customer account summary, shown only when the wallet is in play --}}
    @if ($invoice->hasWalletActivity())
        <div class="mt-6 flex justify-end">
            <table class="w-88 text-[13px]">
                <tbody>
                    <tr>
                        <td class="py-1 text-gray-500">Previous Balance</td>
                        <td class="py-1 text-right tabular-nums text-slate-800">{{ number_format($openingBalance, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Amount Received</td>
                        <td class="py-1 text-right tabular-nums text-slate-800">{{ number_format($invoice->receivedAmount(), 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">This Invoice</td>
                        <td class="py-1 text-right tabular-nums text-slate-800">({{ number_format((float) $totalAmount, 2) }})</td>
                    </tr>
                    <tr class="border-t border-slate-800">
                        <td class="pt-2 text-xs font-semibold uppercase tracking-[0.15em] text-gray-500">
                            {{ $closingBalance < 0 ? 'Balance Due' : 'Balance In Hand' }} ({{ $invoice->currency->code }})
                        </td>
                        <td class="pt-2 text-right text-base font-bold tabular-nums text-slate-900">
                            {{ number_format(abs($closingBalance), 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    {{-- Terms & conditions --}}
    @if ($terms)
        <div class="mt-12 text-[13px] leading-relaxed text-gray-500">
            <p class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Terms &amp; Conditions</p>
            <p class="whitespace-pre-line">{{ $terms }}</p>
        </div>
    @endif

    {{-- Payment details + signature --}}
    <div class="{{ $terms ? 'mt-8' : 'mt-14' }} flex items-end justify-between gap-10 border-t border-gray-200 pt-7">
        <div class="text-[13px] leading-loose text-gray-500">
            @if ($bankRows !== [])
                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Payment Details</p>
                @foreach ($bankRows as $label => $value)
                    <p>
                        <span class="inline-block w-32">{{ $label }}</span>
                        <span class="font-medium text-slate-800">{{ $value }}</span>
                    </p>
                @endforeach
            @endif
        </div>
        <div class="shrink-0 pb-1 text-center">
            <div class="w-64 border-t-2 border-slate-800"></div>
            @if ($signatoryName)
                <p class="mt-2 text-base font-semibold text-slate-800">{{ $signatoryName }}</p>
                @if ($signatoryDesignation)
                    <p class="mt-0.5 text-xs text-gray-500">{{ $signatoryDesignation }}</p>
                @endif
            @else
                <p class="mt-2 text-base text-slate-800">Authorized Signature</p>
            @endif
            <p class="mt-0.5 text-xs text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>

    {{-- Footer note --}}
    @if ($footerNote)
        <p x-show="letterhead" class="mt-12 text-center text-[13px] italic text-gray-400">
            {{ $footerNote }}
        </p>
    @endif
</x-print-page>
