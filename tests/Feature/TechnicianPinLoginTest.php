<?php

namespace Tests\Feature;

use App\Models\Technician;
use App\Models\TechnicianPortalAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TechnicianPinLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Technician $technician;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->technician = Technician::first();
        $this->adminUser = User::first();
    }

    /**
     * Test technician login page renders with Security PIN components without default initial PIN.
     */
    public function test_login_page_renders_with_pin_components_without_default(): void
    {
        $response = $this->get(route('tech_portal.login'));
        $response->assertStatus(200);
        $response->assertSee('Field Technician Portal');
        $response->assertSee('Technician PIN Access');
        $response->assertSee('Security PIN');
        $response->assertSee('Sign In to Technician Portal');
        $response->assertSee($this->technician->full_name);
        $response->assertSee($this->technician->phone);

        // Verify no hardcoded default 1234 in input
        $response->assertDontSee('value="1234"', false);
        $response->assertDontSee('Default initial PIN is');

        // Confirm OTP elements are removed
        $response->assertDontSee('Get Login OTP');
        $response->assertDontSee('Enter 6-Digit OTP');
        $response->assertDontSee('Resend OTP');
    }

    /**
     * Test authentication fails for unregistered phone number.
     */
    public function test_authenticate_fails_for_unregistered_phone(): void
    {
        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => '0000000000',
            'pin' => '5821',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Technician phone number not found in directory.',
        ]);
        $this->assertNull(session('technician_id'));
    }

    /**
     * Test authentication fails when technician has not been assigned a PIN by admin.
     */
    public function test_authenticate_fails_when_no_pin_assigned_by_admin(): void
    {
        // Remove portal access / pin
        if ($this->technician->portalAccess) {
            $this->technician->portalAccess->delete();
        }

        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'No Security PIN has been assigned to your account yet. Please contact your administrator to set up your PIN.',
        ]);
        $this->assertNull(session('technician_id'));
    }

    /**
     * Test fallback to 1234 or 0000 is rejected if not assigned.
     */
    public function test_default_pin_1234_is_rejected_when_not_assigned(): void
    {
        TechnicianPortalAccess::updateOrCreate(
            ['technician_id' => $this->technician->id],
            [
                'portal_enabled' => true,
                'pin_hash' => Hash::make('9482'),
            ]
        );

        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid Security PIN. Please enter your correct PIN or contact your administrator.',
        ]);
        $this->assertNull(session('technician_id'));
    }

    /**
     * Test authentication succeeds only with the admin-assigned separate PIN.
     */
    public function test_authenticate_succeeds_with_admin_assigned_pin(): void
    {
        TechnicianPortalAccess::updateOrCreate(
            ['technician_id' => $this->technician->id],
            [
                'portal_enabled' => true,
                'pin_hash' => Hash::make('6291'),
            ]
        );

        // Correct PIN
        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '6291',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('tech_portal.dashboard'),
        ]);

        $response->assertSessionHas('technician_id', $this->technician->id);

        // Confirm dashboard access
        $dashResponse = $this->get(route('tech_portal.dashboard'));
        $dashResponse->assertStatus(200);
    }

    /**
     * Test admin can assign a separate PIN for a technician via admin panel.
     */
    public function test_admin_can_assign_separate_pin_for_technician(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('technicians.reset_pin', $this->technician->id), [
                'pin' => '7143',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Technician Portal PIN updated successfully.',
            ]);

        $this->technician->refresh();
        $this->assertTrue(Hash::check('7143', $this->technician->portalAccess->pin_hash));

        // Now technician logs in with new admin-assigned PIN
        $loginResponse = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '7143',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertSessionHas('technician_id', $this->technician->id);
    }

    /**
     * Test authentication fails when portal access is disabled.
     */
    public function test_authenticate_fails_when_portal_disabled(): void
    {
        TechnicianPortalAccess::updateOrCreate(
            ['technician_id' => $this->technician->id],
            [
                'portal_enabled' => false,
                'pin_hash' => Hash::make('7143'),
            ]
        );

        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '7143',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Your portal access is disabled. Please contact administrator.',
        ]);
        $this->assertNull(session('technician_id'));
    }

    /**
     * Test logout clears technician session.
     */
    public function test_logout_clears_technician_session(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.logout'));

        $response->assertRedirect(route('tech_portal.login'));
        $this->assertNull(session('technician_id'));
    }
}
