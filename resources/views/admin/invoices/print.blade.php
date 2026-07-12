@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $bankName = \App\Models\Setting::get('bank_name');
    $bankAccountName = \App\Models\Setting::get('bank_account_name');
    $bankAccountNumber = \App\Models\Setting::get('bank_account_number');
    $bankBranch = \App\Models\Setting::get('bank_branch');
    $footerNote = \App\Models\Setting::get('invoice_footer_note');
    $hasBankDetails = $bankName || $bankAccountName || $bankAccountNumber || $bankBranch;
@endphp

<x-print-layout :title="'Invoice '.$invoice->invoice_no" :back-url="route('admin.invoices.show', $invoice)">
    <x-slot:letterhead>
        <x-print-letterhead heading="Invoice" />
    </x-slot:letterhead>

    <div class="mb-10 flex items-end justify-between gap-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Billed To</p>
            <p class="mt-1.5 text-lg font-semibold text-gray-900">{{ $invoice->bill_to }}</p>
        </div>
        <dl class="text-right text-sm">
            <div class="flex justify-end gap-6">
                <dt class="text-gray-400">Invoice No</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $invoice->invoice_no }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Issue Date</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $invoice->invoice_date->format('d M Y') }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Currency</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $invoice->currency->code }}</dd>
            </div>
        </dl>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b-2 border-gray-900 text-[11px] uppercase tracking-widest text-gray-500">
                <th class="pb-2.5 text-left font-semibold">Description</th>
                <th class="pb-2.5 text-right font-semibold">Qty / Weight</th>
                <th class="pb-2.5 text-right font-semibold">Rate</th>
                <th class="pb-2.5 text-right font-semibold">Amount ({{ $invoice->currency->code }})</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="py-3 pr-4 text-gray-900">{{ $item->description }}</td>
                    <td class="py-3 text-right tabular-nums text-gray-600">
                        {{ $item->quantity !== null ? number_format((float) $item->quantity, 2) : '—' }}
                    </td>
                    <td class="py-3 text-right tabular-nums text-gray-600">
                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '—' }}
                    </td>
                    <td class="py-3 text-right font-medium tabular-nums text-gray-900">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-8 flex justify-end">
        <div class="w-72">
            <div class="flex items-baseline justify-between border-t-2 border-gray-900 pt-3">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-gray-500">Total</span>
                <span class="text-2xl font-semibold tabular-nums text-gray-900">
                    {{ $invoice->currency->symbol }}{{ number_format((float) $totalAmount, 2) }}
                </span>
            </div>
            <p class="mt-1.5 text-right text-[11px] text-gray-400">
                Amount in {{ $invoice->currency->name }} ({{ $invoice->currency->code }}) only
            </p>
        </div>
    </div>

    <div class="mt-16 flex items-end justify-between gap-10 border-t border-gray-200 pt-6">
        <div class="text-[11px] leading-relaxed text-gray-500">
            @if ($hasBankDetails)
                <p class="font-semibold uppercase tracking-widest text-gray-400">Payment Details</p>
                @if ($bankName)
                    <p class="mt-1.5"><span class="inline-block w-24">Bank</span><span class="text-gray-900">{{ $bankName }}</span></p>
                @endif
                @if ($bankAccountName)
                    <p><span class="inline-block w-24">Account Name</span><span class="text-gray-900">{{ $bankAccountName }}</span></p>
                @endif
                @if ($bankAccountNumber)
                    <p><span class="inline-block w-24">Account No.</span><span class="text-gray-900">{{ $bankAccountNumber }}</span></p>
                @endif
                @if ($bankBranch)
                    <p><span class="inline-block w-24">Branch</span><span class="text-gray-900">{{ $bankBranch }}</span></p>
                @endif
            @endif
        </div>
        <div class="shrink-0 text-center">
            <div class="w-52 border-t border-gray-900 pt-2 text-sm text-gray-700">Authorized Signature</div>
            <p class="mt-1 text-[11px] text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>

    @if ($footerNote)
        <p x-show="letterhead" class="mt-10 text-center text-[11px] italic text-gray-400">
            {{ $footerNote }}
        </p>
    @endif
</x-print-layout>
