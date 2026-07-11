<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\NewCustomerRegistered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the customer registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new customer account pending admin approval.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $customer = User::create([
            ...$request->validated(),
            'type' => UserType::Customer,
            'status' => UserStatus::Pending,
        ]);

        $approvers = User::query()
            ->staff()
            ->get()
            ->filter(fn (User $user): bool => $user->can('customers.approve'));

        Notification::send($approvers, new NewCustomerRegistered($customer));

        return redirect()
            ->route('login')
            ->with('status', 'Registration successful! Your account is awaiting admin approval.');
    }
}
