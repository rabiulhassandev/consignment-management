<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settings_page_renders_every_billing_document_field(): void
    {
        $staff = $this->createStaffUser('settings.manage');

        $response = $this->actingAs($staff)->get(route('admin.settings.edit'))->assertOk();

        foreach ([
            'company_name', 'company_tagline', 'company_website', 'company_registration_no',
            'bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch',
            'bank_swift_code', 'bank_routing_number',
            'invoice_payment_terms', 'invoice_terms',
            'invoice_signatory_name', 'invoice_signatory_designation', 'invoice_footer_note',
        ] as $field) {
            $response->assertSee('name="'.$field.'"', escape: false);
        }
    }

    public function test_staff_with_permission_can_update_settings(): void
    {
        $staff = $this->createStaffUser('settings.manage');

        $response = $this->actingAs($staff)->put(route('admin.settings.update'), [
            'site_name' => 'BNoor Group',
            'site_email' => 'info@bnoorgroup.com',
            'site_phone' => '01700000000',
            'site_address' => 'Dhaka, Bangladesh',
            'company_name' => 'Guangzhou Bnoor Global Trading Company Limited',
            'bank_name' => 'The City Bank Limited',
            'bank_account_number' => '1504311841001',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('BNoor Group', Setting::get('site_name'));
        $this->assertSame('info@bnoorgroup.com', Setting::get('site_email'));
        $this->assertSame('Guangzhou Bnoor Global Trading Company Limited', Setting::get('company_name'));
        $this->assertSame('1504311841001', Setting::get('bank_account_number'));
    }

    public function test_staff_can_update_invoice_document_settings(): void
    {
        $staff = $this->createStaffUser('settings.manage');

        $this->actingAs($staff)
            ->put(route('admin.settings.update'), [
                'site_name' => 'BNoor Group',
                'company_registration_no' => 'BIN 004561234-0101',
                'bank_swift_code' => 'CIBLBDDH',
                'bank_routing_number' => '225264535',
                'invoice_payment_terms' => 'Payment due within 15 days of invoice date.',
                'invoice_terms' => "1. Goods once sold are not returnable.\n2. Payment by TT to the account above.",
                'invoice_signatory_name' => 'Mahbub Rahman',
                'invoice_signatory_designation' => 'Managing Director',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('BIN 004561234-0101', Setting::get('company_registration_no'));
        $this->assertSame('CIBLBDDH', Setting::get('bank_swift_code'));
        $this->assertSame('225264535', Setting::get('bank_routing_number'));
        $this->assertSame('Payment due within 15 days of invoice date.', Setting::get('invoice_payment_terms'));
        $this->assertStringContainsString('not returnable', (string) Setting::get('invoice_terms'));
        $this->assertSame('Mahbub Rahman', Setting::get('invoice_signatory_name'));
        $this->assertSame('Managing Director', Setting::get('invoice_signatory_designation'));
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
