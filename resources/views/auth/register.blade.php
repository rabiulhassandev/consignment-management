<x-guest-layout title="Register">
    <h1 class="text-xl font-semibold tracking-tight text-gray-900">Create your account</h1>
    <p class="mt-1 text-sm text-gray-500">Register as a customer. An admin will review and approve your account.</p>

    <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
        @csrf

        <x-form.input name="name" label="Full name" required autofocus autocomplete="name" />
        <x-form.input name="email" type="email" label="Email address" required autocomplete="username" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.input name="phone" label="Phone" autocomplete="tel" />
            <x-form.input name="company_name" label="Company name" autocomplete="organization" />
        </div>

        <x-form.input name="address" label="Address" autocomplete="street-address" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />

        <x-button type="submit" class="w-full">Create account</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Log in</a>
    </p>
</x-guest-layout>
