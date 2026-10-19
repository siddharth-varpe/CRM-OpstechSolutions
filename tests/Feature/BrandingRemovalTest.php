<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\SystemSetting;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->first();
    }

    /**
     * Test auth & public pages do not contain 'SR Enterprises'.
     */
    public function test_auth_and_login_pages_do_not_contain_sr_enterprises(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertDontSee('SR Enterprises', false);
        $response->assertDontSee('srenterprises', false);
        $response->assertSee('Enterprises CRM');

        $techPortalResponse = $this->get(route('tech_portal.login'));
        $techPortalResponse->assertStatus(200);
        $techPortalResponse->assertDontSee('SR Enterprises', false);
        $techPortalResponse->assertDontSee('srenterprises', false);
        $techPortalResponse->assertSee('Enterprises CRM');
    }

    /**
     * Test admin dashboard and technician pages do not contain 'SR Enterprises'.
     */
    public function test_crm_views_do_not_contain_sr_enterprises(): void
    {
        // 1. Dashboard
        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertDontSee('SR Enterprises', false);
        $dashResponse->assertDontSee('srenterprises', false);

        // 2. Technicians list & map
        $techListResponse = $this->actingAs($this->admin)->get(route('technicians.index'));
        $techListResponse->assertStatus(200);
        $techListResponse->assertDontSee('SR Enterprises', false);
        $techListResponse->assertDontSee('srenterprises', false);

        $techMapResponse = $this->actingAs($this->admin)->get(route('technicians.map'));
        $techMapResponse->assertStatus(200);
        $techMapResponse->assertDontSee('SR Enterprises', false);
        $techMapResponse->assertDontSee('srenterprises', false);

        // 3. Technician profile
        $tech = Technician::first();
        if ($tech) {
            $techShowResponse = $this->actingAs($this->admin)->get(route('technicians.show', $tech->id));
            $techShowResponse->assertStatus(200);
            $techShowResponse->assertDontSee('SR Enterprises', false);
            $techShowResponse->assertDontSee('srenterprises', false);
        }

        // 4. Dues management
        $duesResponse = $this->actingAs($this->admin)->get(route('dues.index'));
        $duesResponse->assertStatus(200);
        $duesResponse->assertDontSee('SR Enterprises', false);
        $duesResponse->assertDontSee('srenterprises', false);
    }

    /**
     * Test invoice, job card, and settings pages do not contain 'SR Enterprises'.
     */
    public function test_invoices_job_cards_and_settings_do_not_contain_sr_enterprises(): void
    {
        // 1. Invoices
        $invoice = Invoice::first();
        if ($invoice) {
            $invResponse = $this->actingAs($this->admin)->get(route('invoices.show', $invoice->id));
            $invResponse->assertStatus(200);
            $invResponse->assertDontSee('SR Enterprises', false);
            $invResponse->assertDontSee('SR ENTERPRISES', false);
            $invResponse->assertDontSee('srenterprises', false);
        }

        // 2. Job Card
        $jobCard = JobCard::first();
        if ($jobCard) {
            $jcResponse = $this->actingAs($this->admin)->get(route('job_cards.show', $jobCard->id));
            $jcResponse->assertStatus(200);
            $jcResponse->assertDontSee('SR Enterprises', false);
            $jcResponse->assertDontSee('SR ENTERPRISES', false);
            $jcResponse->assertDontSee('srenterprises', false);
        }

        // 3. Settings
        $settingsResponse = $this->actingAs($this->admin)->get(route('settings.index'));
        $settingsResponse->assertStatus(200);
        $settingsResponse->assertDontSee('SR Enterprises', false);
        $settingsResponse->assertDontSee('SR ENTERPRISES', false);
        $settingsResponse->assertDontSee('srenterprises.com', false);
    }

    /**
     * Test database seed records contain no srenterprises references.
     */
    public function test_database_records_have_no_sr_enterprises(): void
    {
        $usersWithOldDomain = User::where('email', 'LIKE', '%srenterprises%')->count();
        $this->assertEquals(0, $usersWithOldDomain, 'No user should have @srenterprises email.');

        $companySetting = SystemSetting::get('company_name');
        $this->assertStringNotContainsString('SR Enterprises', $companySetting);
        $this->assertEquals('Enterprises CRM', $companySetting);
    }

    /**
     * Test developer branding 'Developed by Kartik & Siddharth' is removed and version 1.0.0 is displayed.
     */
    public function test_branding_does_not_contain_kartik_and_siddharth_and_shows_version(): void
    {
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertDontSee('Developed by Kartik & Siddharth', false);
        $loginResponse->assertDontSee('Kartik', false);
        $loginResponse->assertSee('v1.0.0');

        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertDontSee('Developed by Kartik & Siddharth', false);
        $dashResponse->assertDontSee('Kartik', false);
        $dashResponse->assertSee('v1.0.0');

        $techLogin = $this->get(route('tech_portal.login'));
        $techLogin->assertStatus(200);
        $techLogin->assertDontSee('Developed by Kartik & Siddharth', false);
        $techLogin->assertDontSee('Kartik', false);
        $techLogin->assertSee('v1.0.0');
    }
}
