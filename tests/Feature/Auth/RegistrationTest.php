<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\NewCustomerRegistered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_page_is_accessible(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_customer_can_register_and_is_pending_approval(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '0123456789',
            'company_name' => 'Jane Trading',
            'address' => 'Dhaka, Bangladesh',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $customer = User::query()->where('email', 'jane@example.com')->first();

        $this->assertNotNull($customer);
        $this->assertSame(UserType::Customer, $customer->type);
        $this->assertSame(UserStatus::Pending, $customer->status);
        $this->assertGuest();
    }

    public function test_registration_notifies_staff_who_can_approve_customers(): void
    {
        $approver = $this->createStaffUser('customers.approve');
        $otherStaff = $this->createStaffUser();

        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Notification::assertSentTo($approver, NewCustomerRegistered::class);
        Notification::assertNotSentTo($otherStaff, NewCustomerRegistered::class);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->customer()->create(['email' => 'jane@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }
}
