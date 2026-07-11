<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_with_permission_can_update_settings(): void
    {
        $staff = $this->createStaffUser('settings.manage');

        $response = $this->actingAs($staff)->put(route('admin.settings.update'), [
            'site_name' => 'BNoor Group',
            'site_email' => 'info@bnoorgroup.com',
            'site_phone' => '01700000000',
            'site_address' => 'Dhaka, Bangladesh',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('BNoor Group', Setting::get('site_name'));
        $this->assertSame('info@bnoorgroup.com', Setting::get('site_email'));
    }

    public function test_site_name_is_required(): void
    {
        $staff = $this->createStaffUser('settings.manage');

        $this->actingAs($staff)
            ->put(route('admin.settings.update'), ['site_name' => ''])
            ->assertSessionHasErrors('site_name');
    }

    public function test_staff_without_permission_cannot_access_settings(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }
}
