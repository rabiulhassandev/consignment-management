@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $bankName = \App\Models\Setting::get('bank_name');
    $bankAccountName = \App\Models\Setting::get('bank_account_name');
    $bankAccountNumber = \App\Models\Setting::get('bank_account_number');
    $bankBranch = \App\Models\Setting::get('bank_branch');
    $footerNote = \App\Models\Setting::get('invoice_footer_note');
    $hasBankDetails = $bankName || $bankAccountName || $bankAccountNumber || $bankBranch;
@endphp

<x-print-page :title="'Invoice '.$invoice->invoice_no" :back-url="route('admin.invoices.show', $invoice)" heading="Invoice">
    {{-- Billed to + invoice meta --}}
    <div class="mt-9 flex items-start justify-between gap-8">
        <div class="flex items-start gap-4">
            <p class="pt-1 text-[13px] font-semibold uppercase tracking-[0.2em] text-gray-400">Billed To</p>
            <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $invoice->bill_to }}</p>
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
            <p class="mt-2 text-right text-xs text-gray-400">
                Amount in {{ $invoice->currency->name }} ({{ $invoice->currency->code }}) only
            </p>
        </div>
    </div>

    {{-- Payment details + signature --}}
    <div class="mt-14 flex items-end justify-between gap-10 border-t border-gray-200 pt-7">
        <div class="text-[13px] leading-loose text-gray-500">
            @if ($hasBankDetails)
                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Payment Details</p>
                @if ($bankName)
                    <p><span class="inline-block w-32">Bank</span><span class="font-medium text-slate-800">{{ $bankName }}</span></p>
                @endif
                @if ($bankAccountName)
                    <p><span class="inline-block w-32">Account Name</span><span class="font-medium text-slate-800">{{ $bankAccountName }}</span></p>
                @endif
                @if ($bankAccountNumber)
                    <p><span class="inline-block w-32">Account No.</span><span class="font-medium text-slate-800">{{ $bankAccountNumber }}</span></p>
                @endif
                @if ($bankBranch)
                    <p><span class="inline-block w-32">Branch</span><span class="font-medium text-slate-800">{{ $bankBranch }}</span></p>
                @endif
            @endif
        </div>
        <div class="shrink-0 pb-1 text-center">
            <div class="w-64 border-t-2 border-slate-800"></div>
            <p class="mt-2 text-base text-slate-800">Authorized Signature</p>
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
