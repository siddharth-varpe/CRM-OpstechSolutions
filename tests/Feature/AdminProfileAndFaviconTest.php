<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProfileAndFaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Test admin user identity and that 'Ramesh Bomble' is completely removed.
     */
    public function test_admin_user_has_correct_identity_without_ramesh_bomble(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin, 'Admin user must exist.');
        $this->assertNotEquals('Ramesh Bomble', $admin->display_name, 'Admin display name must not be Ramesh Bomble.');
        $this->assertEquals('System Administrator', $admin->display_name);

        // Authenticate as admin and verify topbar renders System Administrator and does not mention Ramesh Bomble
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('System Administrator');
        $response->assertDontSee('Ramesh Bomble');
    }

    /**
     * Test that the official favicon is linked in HTML layouts and served with HTTP 200.
     */
    public function test_official_favicon_is_present_and_linked(): void
    {
        // 1. Verify layout has favicon links
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('favicon.ico');
        $response->assertSee('apple-touch-icon.png');

        // 2. Verify favicon.ico exists in public directory and has official size (> 10KB)
        $faviconPath = public_path('favicon.ico');
        $this->assertFileExists($faviconPath);
        $this->assertGreaterThan(10000, filesize($faviconPath), 'Favicon should be the official multi-resolution icon (> 10KB)');
    }
}
