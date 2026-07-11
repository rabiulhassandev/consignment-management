@if (session('status') || session('success') || session('error'))
    @php
        $message = session('status') ?? session('success') ?? session('error');
        $isError = session()->has('error');
    @endphp
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-cloak
         @class([
            'mb-6 flex items-start justify-between gap-3 rounded-lg border p-4 text-sm',
            'border-green-200 bg-green-50 text-green-800' => ! $isError,
            'border-red-200 bg-red-50 text-red-800' => $isError,
         ])>
        <div class="flex items-center gap-2">
            <x-icon :name="$isError ? 'x-mark' : 'check'" class="size-5" />
            <span>{{ $message }}</span>
        </div>
        <button type="button" @click="show = false" class="opacity-60 transition-opacity hover:opacity-100">
            <x-icon name="x-mark" class="size-4" />
        </button>
    </div>
@endif
