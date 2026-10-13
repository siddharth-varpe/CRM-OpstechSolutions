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
        $response->assertSee('Field Technician Portal');
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

        // 2. Tab Panes (All 5 pages in tab form)
        $response->assertSee('id="tabPane-tasks"', false);
        $response->assertSee('id="tabPane-navigation"', false);
        $response->assertSee('id="tabPane-execute"', false);
        $response->assertSee('id="tabPane-history"', false);
        $response->assertSee('id="tabPane-profile"', false);

        // 3. Top Tab triggers
        $response->assertSee('id="tabBtn-tasks"', false);
        $response->assertSee('id="tabBtn-navigation"', false);
        $response->assertSee('id="tabBtn-execute"', false);
        $response->assertSee('id="tabBtn-history"', false);
        $response->assertSee('id="tabBtn-profile"', false);

        // 4. Mobile Bottom App Navigation Bar
        $response->assertSee('id="botNav-tasks"', false);
        $response->assertSee('id="botNav-navigation"', false);
        $response->assertSee('id="botNav-execute"', false);
        $response->assertSee('id="botNav-history"', false);
        $response->assertSee('id="botNav-profile"', false);

        // 5. Interactive Components
        $response->assertSee('id="mapPortalNav"', false); // Leaflet map container
        $response->assertSee('id="canvasSignature"', false); // Touch signature pad
        $response->assertSee('id="formCompleteJobCard"', false); // Job card form
    }

    /**
     * Test navigation and execute tabs with query parameters.
     */
    public function test_dashboard_supports_tab_query_parameters(): void
    {
        $service = Service::where('technician_id', $this->technician->id)->first();
        $serviceId = $service ? $service->id : '';

        // Open navigation tab directly
        $navResponse = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard', ['tab' => 'navigation', 'service_id' => $serviceId]));
        $navResponse->assertStatus(200);
        $navResponse->assertSee('Live Field Navigation');

        // Open execution tab directly
        $execResponse = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard', ['tab' => 'execute', 'service_id' => $serviceId]));
        $execResponse->assertStatus(200);
        $execResponse->assertSee('Execute Job Card');
    }
}
