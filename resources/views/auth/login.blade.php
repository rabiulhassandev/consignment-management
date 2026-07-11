<x-guest-layout title="Log in">
    <h1 class="text-xl font-semibold tracking-tight text-gray-900">Welcome back</h1>
    <p class="mt-1 text-sm text-gray-500">Log in to your account to continue.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf

        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
            Remember me
        </label>

        <x-button type="submit" class="w-full">Log in</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        New customer?
        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Create an account</a>
    </p>
</x-guest-layout>
