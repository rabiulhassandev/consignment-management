<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? "$title — " : '' }}{{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
<div x-data="{ sidebarOpen: false }">
    {{-- Mobile sidebar backdrop --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-gray-950/60 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gray-900 transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-lg bg-linear-to-br from-indigo-500 to-violet-600 text-base font-bold text-white shadow-lg shadow-indigo-900/50">
                    {{ str($siteName)->substr(0, 1)->upper() }}
                </span>
                <span class="truncate text-base font-semibold tracking-tight text-white">{{ $siteName }}</span>
            </a>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3 scrollbar-thin [scrollbar-color:var(--color-gray-700)_transparent]">
            <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Main</p>

            <x-nav-item variant="dark" :href="route('admin.dashboard')" icon="home" :active="request()->routeIs('admin.dashboard')">
                Dashboard
            </x-nav-item>

            @can('customers.view')
                @if (Route::has('admin.customers.index'))
                    <x-nav-item variant="dark" :href="route('admin.customers.index')" icon="users" :active="request()->routeIs('admin.customers.*')">
                        Customers
                    </x-nav-item>
                @endif
            @endcan

            @can('consignments.view')
                @if (Route::has('admin.consignments.index'))
                    <x-nav-item variant="dark" :href="route('admin.consignments.index')" icon="cube" :active="request()->routeIs('admin.consignments.*')">
                        Consignments
                    </x-nav-item>
                @endif
            @endcan

            @can('categories.manage')
                @if (Route::has('admin.categories.index'))
                    <x-nav-item variant="dark" :href="route('admin.categories.index')" icon="tag" :active="request()->routeIs('admin.categories.*')">
                        Categories
                    </x-nav-item>
                @endif
            @endcan

            @can('currencies.manage')
                @if (Route::has('admin.currencies.index'))
                    <x-nav-item variant="dark" :href="route('admin.currencies.index')" icon="currency" :active="request()->routeIs('admin.currencies.*')">
                        Currencies
                    </x-nav-item>
                @endif
            @endcan

            @canany(['invoices.view', 'sales-contracts.view', 'lc-bills.view', 'tt-accounts.view'])
                <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Billing</p>

                @can('invoices.view')
                    @if (Route::has('admin.invoices.index'))
                        <x-nav-item variant="dark" :href="route('admin.invoices.index')" icon="receipt" :active="request()->routeIs('admin.invoices.*')">
                            Invoices
                        </x-nav-item>
                    @endif
                @endcan

                @can('sales-contracts.view')
                    @if (Route::has('admin.sales-contracts.index'))
                        <x-nav-item variant="dark" :href="route('admin.sales-contracts.index')" icon="document" :active="request()->routeIs('admin.sales-contracts.*')">
                            Sales Contracts
                        </x-nav-item>
                    @endif
                @endcan

                @can('lc-bills.view')
                    @if (Route::has('admin.lc-bills.index'))
                        <x-nav-item variant="dark" :href="route('admin.lc-bills.index')" icon="banknotes" :active="request()->routeIs('admin.lc-bills.*')">
                            LC Bills
                        </x-nav-item>
                    @endif
                @endcan

                @can('tt-accounts.view')
                    @if (Route::has('admin.tt-accounts.index'))
                        <x-nav-item variant="dark" :href="route('admin.tt-accounts.index')" icon="book-open" :active="request()->routeIs('admin.tt-accounts.*')">
                            TT Accounts
                        </x-nav-item>
                    @endif
                @endcan
            @endcanany

            @canany(['transactions.view', 'transaction-categories.manage'])
                <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Finance</p>

                @can('transactions.view')
                    @if (Route::has('admin.income-expense.index'))
                        <x-nav-item variant="dark" :href="route('admin.income-expense.index')" icon="wallet" :active="request()->routeIs('admin.income-expense.index')">
                            Income &amp; Expense
                        </x-nav-item>
                    @endif

                    @if (Route::has('admin.transactions.index'))
                        <x-nav-item variant="dark" :href="route('admin.transactions.index')" icon="banknotes" :active="request()->routeIs('admin.transactions.*')">
                            Transactions
                        </x-nav-item>
                    @endif

                    @if (Route::has('admin.income-expense.report'))
                        <x-nav-item variant="dark" :href="route('admin.income-expense.report')" icon="chart-bar" :active="request()->routeIs('admin.income-expense.report*')">
                            Reports
                        </x-nav-item>
                    @endif
                @endcan

                @can('transaction-categories.manage')
                    @if (Route::has('admin.transaction-categories.index'))
                        <x-nav-item variant="dark" :href="route('admin.transaction-categories.index')" icon="tag" :active="request()->routeIs('admin.transaction-categories.*')">
                            Categories
                        </x-nav-item>
                    @endif
                @endcan
            @endcanany

            @canany(['users.manage', 'roles.manage', 'settings.manage'])
                <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Administration</p>

                @can('users.manage')
                    @if (Route::has('admin.users.index'))
                        <x-nav-item variant="dark" :href="route('admin.users.index')" icon="user-group" :active="request()->routeIs('admin.users.*')">
                            Users
                        </x-nav-item>
                    @endif
                @endcan

                @can('roles.manage')
                    @if (Route::has('admin.roles.index'))
                        <x-nav-item variant="dark" :href="route('admin.roles.index')" icon="shield" :active="request()->routeIs('admin.roles.*')">
                            Roles
                        </x-nav-item>
                    @endif
                @endcan

                @can('settings.manage')
                    @if (Route::has('admin.settings.edit'))
                        <x-nav-item variant="dark" :href="route('admin.settings.edit')" icon="cog" :active="request()->routeIs('admin.settings.*')">
                            Settings
                        </x-nav-item>
                    @endif
                @endcan
            @endcanany
        </nav>

        <div class="border-t border-white/10 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">
                    {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200/75 bg-white/80 px-4 backdrop-blur-md sm:gap-4 sm:px-6">
            <button type="button" @click="sidebarOpen = true"
                    class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 lg:hidden">
                <x-icon name="menu" class="size-6" />
                <span class="sr-only">Open sidebar</span>
            </button>

            @if (Route::has('admin.search') && auth()->user()->can('consignments.view'))
                <form method="GET" action="{{ route('admin.search') }}" class="relative max-w-md flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Search sample no, own sample, consignment no…"
                           class="block w-full rounded-lg border-0 bg-gray-100 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-transparent transition placeholder:text-gray-400 focus:bg-white focus:ring-indigo-600">
                </form>
            @endif

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                {{-- Notifications bell --}}
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open"
                            class="relative rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700">
                        <x-icon name="bell" />
                        <span class="sr-only">Notifications</span>
                        @if ($unreadCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1 py-0.5 text-xs font-semibold text-white ring-2 ring-white">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </button>

                    <div x-cloak x-show="open" x-transition @click.outside="open = false"
                         class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200/75 bg-white shadow-lg shadow-gray-900/5">
                        <div class="flex items-center justify-between border-b border-gray-200/75 px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">Notifications</p>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route('admin.notifications.read') }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-700">
                                        Mark all as read
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto">
                            @forelse ($unreadNotifications as $notification)
                                @php
                                    $customerId = $notification->data['customer_id'] ?? null;
                                    $link = ($customerId && Route::has('admin.customers.show')) ? route('admin.customers.show', $customerId) : null;
                                @endphp
                                <a @if ($link) href="{{ $link }}" @endif class="flex gap-3 px-4 py-3 transition-colors hover:bg-gray-50">
                                    <span class="mt-1 flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-50">
                                        <x-icon name="users" class="size-4 text-indigo-600" />
                                    </span>
                                    <span class="min-w-0">
                                        <p class="text-sm text-gray-800">{{ $notification->data['message'] ?? 'Notification' }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                                    </span>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-gray-500">No unread notifications.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="hidden h-6 w-px bg-gray-200 sm:block"></div>

                {{-- User menu --}}
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open"
                            class="flex items-center gap-2 rounded-lg p-1.5 text-sm transition-colors hover:bg-gray-100">
                        <span class="flex size-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                        </span>
                        <span class="hidden font-medium text-gray-700 sm:block">{{ auth()->user()->name }}</span>
                        <x-icon name="chevron-down" class="hidden size-4 text-gray-400 sm:block" />
                    </button>

                    <div x-cloak x-show="open" x-transition @click.outside="open = false"
                         class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200/75 bg-white shadow-lg shadow-gray-900/5">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                                <x-icon name="logout" class="size-4 text-gray-400" />
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
</body>
</html>
