@php
    use App\Enums\ConversionOperation;

    /** @var \App\Models\TtAccountEntry|null $entry */
    $entry = $entry ?? null;
    $prefix = $modalName;
    $selectedOperation = old('source_operation', $entry?->sourceOperation()->value ?? ConversionOperation::Multiply->value);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4"
      x-data="{
          sourceAmount: {{ Js::from((string) old('source_amount', $entry?->source_amount ?? '')) }},
          sourceRate: {{ Js::from((string) old('source_rate', $entry?->source_rate ?? '')) }},
          sourceOperation: {{ Js::from($selectedOperation) }},
          amount: {{ Js::from((string) old('amount', $entry?->amount ?? '')) }},
          convert() {
              const amount = parseFloat(this.sourceAmount);
              const rate = parseFloat(this.sourceRate);
              if (isNaN(amount) || !(rate > 0)) return;
              this.amount = (this.sourceOperation === 'divide' ? amount / rate : amount * rate).toFixed(2);
          },
      }">
    @csrf
    @if ($entry)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalName }}">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="entry_date" :id="$prefix.'-date'" type="date" label="Date"
                      :value="$entry?->entry_date?->format('Y-m-d')" />
        <x-form.select name="type" :id="$prefix.'-type'" label="Type" required>
            @foreach (\App\Enums\EntryType::cases() as $typeOption)
                <option value="{{ $typeOption->value }}"
                        @selected(old('type', $entry?->type->value) === $typeOption->value)>
                    {{ $typeOption->label() }}{{ $typeOption === \App\Enums\EntryType::Received ? ' (credit)' : ' (debit)' }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <x-form.input name="description" :id="$prefix.'-description'" label="Description"
                  :value="$entry?->description" placeholder="e.g. RECEIVED BDT 134810 TK" required />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-form.select name="source_currency_id" :id="$prefix.'-source-currency'" label="Source currency" placeholder="None">
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}"
                        @selected((int) old('source_currency_id', $entry?->source_currency_id) === $currency->id)>
                    {{ $currency->code }} ({{ $currency->symbol }})
                </option>
            @endforeach
        </x-form.select>
        <x-form.input name="source_amount" :id="$prefix.'-source-amount'" type="number" step="0.01" min="0"
                      label="Source amount" placeholder="e.g. 134810" x-model="sourceAmount" @input="convert()" />
        <x-form.select name="source_operation" :id="$prefix.'-source-operation'" label="Operation"
                       x-model="sourceOperation" @change="convert()">
            @foreach (ConversionOperation::cases() as $operationOption)
                <option value="{{ $operationOption->value }}" @selected($selectedOperation === $operationOption->value)>
                    {{ $operationOption->label() }}
                </option>
            @endforeach
        </x-form.select>
        <x-form.input name="source_rate" :id="$prefix.'-source-rate'" type="number" step="0.0001" min="0"
                      label="Rate" placeholder="e.g. 18.00" x-model="sourceRate" @input="convert()" />
    </div>

    <p class="text-xs text-gray-500" x-show="sourceAmount && sourceRate" x-cloak>
        <span x-text="sourceAmount"></span>
        <span x-text="sourceOperation === 'divide' ? '÷' : '×'"></span>
        <span x-text="sourceRate"></span>
        = <span class="font-semibold text-gray-700" x-text="amount"></span>
        {{ $ttAccount->currency->code }}
    </p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="amount" :id="$prefix.'-amount'" type="number" step="0.01" min="0"
                      label="Amount (account currency)" placeholder="0.00" x-model="amount" required />
        <x-form.input name="remarks" :id="$prefix.'-remarks'" label="Remarks"
                      :value="$entry?->remarks" placeholder="e.g. I WILL GET RMB" />
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <x-button variant="secondary" @click="show = false">Cancel</x-button>
        <x-button type="submit">{{ $entry ? 'Save Changes' : 'Add Entry' }}</x-button>
    </div>
</form>
