<x-admin-layout title="Settings">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Global site information used across the application.</p>
    </div>

    <x-card title="Site Information" class="max-w-2xl">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <x-form.input name="site_name" label="Site name" :value="$settings['site_name']" required />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="site_email" type="email" label="Contact email" :value="$settings['site_email']" />
                <x-form.input name="site_phone" label="Contact phone" :value="$settings['site_phone']" />
            </div>

            <x-form.input name="site_address" label="Address" :value="$settings['site_address']" />

            <div>
                <label for="site_logo" class="mb-1.5 block text-sm font-medium text-gray-700">Logo</label>
                @if ($settings['site_logo'])
                    <img src="{{ Storage::url($settings['site_logo']) }}" alt="Site logo" class="mb-2 h-12 w-auto rounded">
                @endif
                <input type="file" name="site_logo" id="site_logo" accept="image/*"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                @error('site_logo')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end pt-2">
                <x-button type="submit">Save Settings</x-button>
            </div>
        </form>
    </x-card>
</x-admin-layout>
