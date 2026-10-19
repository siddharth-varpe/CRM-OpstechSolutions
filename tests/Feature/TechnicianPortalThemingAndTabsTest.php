<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianPortalThemingAndTabsTest extends TestCase
{
    use RefreshDatabase;

    protected Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->technician = Technician::first();
    }

    /**
     * Test technician login page uses admin portal theme and branding.
     */
    public function test_login_page_uses_admin_portal_theme(): void
    {
        $response = $this->get(route('tech_portal.login'));
        $response->assertStatus(200);

        // Verify clean admin styling tokens
        $response->assertSee('bg-[#F4F6F8]', false);
        $response->assertSee('bg-white', false);
        $response->assertSee('ENTERPRISES');
        $response->assertDontSee('Field Technician Portal');
        $response->assertSee('Security PIN');
        $response->assertSee('Sign In to Technician Portal');
        $response->assertDontSee('Get Login OTP');
        $response->assertDontSee('Enter 6-Digit OTP');

        // Verify absence of old dark-neon vibecoded styling
        $response->assertDontSee('bg-slate-950 text-slate-100', false);
    }

    /**
     * Test authenticated technician dashboard renders tab-form pages and mobile bottom nav.
     */
    public function test_dashboard_renders_tabbed_interface_with_mobile_bottom_nav(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard'));

        $response->assertStatus(200);

        // 1. Theme checks (admin theme palette)
        $response->assertSee('bg-workspace', false);
        $response->assertSee('bg-[#00152B]', false); // Navy topbar
        $response->assertSee('ENTERPRISES');
        $response->assertSee($this->technician->full_name);

        // 2. Tab Panes (Assigned tab, history, profile — without inline execution tab or in-app map)
        $response->assertSee('id="tabPane-assigned"', false);
        $response->assertDontSee('id="tabPane-navigation"', false);
        $response->assertDontSee('id="tabPane-execute"', false);
        $response->assertSee('id="tabPane-history"', false);
        $response->assertSee('id="tabPane-profile"', false);

        // 3. Top Tab triggers removed (tabs kept only at bottom)
        $response->assertDontSee('id="tabBtn-assigned"', false);
        $response->assertDontSee('id="tabBtn-history"', false);
        $response->assertDontSee('id="tabBtn-profile"', false);

        // 4. Mobile Bottom App Navigation Bar
        $response->assertSee('id="botNav-assigned"', false);
        $response->assertDontSee('id="botNav-navigation"', false);
        $response->assertDontSee('id="botNav-execute"', false);
        $response->assertSee('id="botNav-history"', false);
        $response->assertSee('id="botNav-profile"', false);

        // 5. Interactive Components (no in-app map or inline execution form)
        $response->assertDontSee('id="mapPortalNav"', false);
        $response->assertDontSee('id="formCompleteJobCard"', false);
    }

    /**
     * Test assigned tab with query parameters, job card execution link, and Google Maps navigation.
     */
    public function test_dashboard_supports_tab_query_parameters_and_job_card_navigation(): void
    {
        $service = Service::where('technician_id', $this->technician->id)->first();
        $this->assertNotNull($service);
        $serviceId = $service->id;

        // Open assigned tab directly
        $assignedResponse = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard', ['tab' => 'assigned']));
        $assignedResponse->assertStatus(200);
        $assignedResponse->assertSee('Assigned Services');
        $assignedResponse->assertSee(route('tech_portal.navigation', $serviceId));
        $assignedResponse->assertSee(route('tech_portal.service', $serviceId));

        // Visit dedicated service execution page
        $execResponse = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.service', $serviceId));
        $execResponse->assertStatus(200);
        $execResponse->assertSee('Job Execution');
        $execResponse->assertSee('Back to Assigned');
        $execResponse->assertSee('sigCanvas');
    }
}
