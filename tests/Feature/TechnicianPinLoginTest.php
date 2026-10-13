<?php

namespace Tests\Feature;

use App\Models\Technician;
use App\Models\TechnicianPortalAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TechnicianPinLoginTest extends TestCase
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
     * Test technician login page renders with Security PIN components.
     */
    public function test_login_page_renders_with_pin_components(): void
    {
        $response = $this->get(route('tech_portal.login'));
        $response->assertStatus(200);
        $response->assertSee('Field Technician Portal');
        $response->assertSee('Technician PIN Access');
        $response->assertSee('Security PIN');
        $response->assertSee('Sign In to Technician Portal');
        $response->assertSee($this->technician->full_name);
        $response->assertSee($this->technician->phone);

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
            'pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Technician phone number not found in directory.',
        ]);
        $this->assertNull(session('technician_id'));
    }

    /**
     * Test authentication fails with wrong Security PIN.
     */
    public function test_authenticate_fails_with_wrong_pin(): void
    {
        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '9999',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid Security PIN. Please enter your correct 4-digit PIN.',
        ]);
        $this->assertNull(session('technician_id'));
    }

    /**
     * Test authentication succeeds with default initial PIN 1234.
     */
    public function test_authenticate_succeeds_with_default_pin_1234(): void
    {
        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '1234',
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
     * Test authentication succeeds with custom configured PIN hash.
     */
    public function test_authenticate_succeeds_with_custom_hashed_pin(): void
    {
        TechnicianPortalAccess::updateOrCreate(
            ['technician_id' => $this->technician->id],
            [
                'portal_enabled' => true,
                'pin_hash' => Hash::make('7890'),
            ]
        );

        // Wrong PIN fails
        $wrongResponse = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '1111',
        ]);
        $wrongResponse->assertStatus(422);

        // Correct custom PIN succeeds
        $correctResponse = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '7890',
        ]);
        $correctResponse->assertStatus(200);
        $correctResponse->assertSessionHas('technician_id', $this->technician->id);
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
                'pin_hash' => Hash::make('1234'),
            ]
        );

        $response = $this->postJson(route('tech_portal.authenticate'), [
            'phone' => $this->technician->phone,
            'pin' => '1234',
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
