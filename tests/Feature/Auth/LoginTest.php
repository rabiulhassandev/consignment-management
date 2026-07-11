<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_staff_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $staff = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_approved_customer_is_redirected_to_portal(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($customer);
    }

    public function test_pending_customer_cannot_login(): void
    {
        $customer = User::factory()->customer()->pending()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rejected_customer_cannot_login(): void
    {
        $customer = User::factory()->customer()->rejected()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
