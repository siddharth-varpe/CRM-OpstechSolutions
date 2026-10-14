<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\JobCard;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianJobHoldTest extends TestCase
{
    use RefreshDatabase;

    protected Technician $technician;
    protected Service $service;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->technician = Technician::first();
        $this->adminUser = User::first();

        // Ensure there is an active assigned service for the technician with a job card
        $this->service = Service::where('technician_id', $this->technician->id)->first();
        if (!$this->service) {
            $this->service = Service::first();
            $this->service->update([
                'technician_id' => $this->technician->id,
                'status' => 'ASSIGNED',
            ]);
        }

        if (!$this->service->jobCard) {
            JobCard::create([
                'service_id' => $this->service->id,
                'technician_id' => $this->technician->id,
                'job_card_number' => 'JC-TEST-001',
                'status' => $this->service->status,
            ]);
        }
    }

    /**
     * Test technician can put job on hold with reason from technician portal.
     */
    public function test_technician_can_put_job_on_hold_with_reason(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.status', $this->service->id), [
                'status' => 'ON_HOLD',
                'reason' => 'Waiting for specialized RO membrane replacement',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ON_HOLD',
            ]);

        $this->service->refresh();
        $this->assertEquals('ON_HOLD', $this->service->status);
        $this->assertStringContainsString('Waiting for specialized RO membrane replacement', $this->service->internal_notes);

        $jobCard = $this->service->jobCard;
        $this->assertNotNull($jobCard);
        $this->assertEquals('ON_HOLD', $jobCard->status);
        $this->assertStringContainsString('Waiting for specialized RO membrane replacement', $jobCard->technician_notes);
    }

    /**
     * Test on-hold job remains visible in technician portal dashboard under assigned tab.
     */
    public function test_on_hold_job_remains_visible_in_technician_assigned_tab(): void
    {
        $this->service->update([
            'status' => 'ON_HOLD',
            'internal_notes' => 'Awaiting spare parts from main warehouse',
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard', ['tab' => 'assigned']));

        $response->assertStatus(200);
        $response->assertSee($this->service->service_number);
        $response->assertSee('ON HOLD');
        $response->assertSee('Resume Job (Currently On Hold)');
        $response->assertSee('filterTaskCards(\'ON_HOLD\')', false);
    }

    /**
     * Test technician can resume job from on hold back to in progress.
     */
    public function test_technician_can_resume_job_from_on_hold_to_in_progress(): void
    {
        $this->service->update(['status' => 'ON_HOLD']);
        if ($this->service->jobCard) {
            $this->service->jobCard->update(['status' => 'ON_HOLD']);
        }

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.status', $this->service->id), [
                'status' => 'IN_PROGRESS',
                'reason' => 'Resumed on-site work by technician',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'IN_PROGRESS',
            ]);

        $this->service->refresh();
        $this->assertEquals('IN_PROGRESS', $this->service->status);

        $jobCard = $this->service->jobCard;
        $this->assertEquals('IN_PROGRESS', $jobCard->status);
    }

    /**
     * Test admin technician profile view displays ON HOLD badge, count, and dedicated tab.
     */
    public function test_admin_technician_panel_displays_on_hold_service_and_tab(): void
    {
        $this->service->update([
            'status' => 'ON_HOLD',
            'internal_notes' => 'Customer requested reschedule',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('technicians.show', $this->technician->id));

        $response->assertStatus(200);
        $response->assertSee('ON HOLD');
        $response->assertSee('On Hold (1)');
        $response->assertSee('id="tab-onHold"', false);
        $response->assertSee('Customer requested reschedule');
        $response->assertSee('1 on hold');
    }

    /**
     * Test admin service view displays ON HOLD badge and on-hold alert banner.
     */
    public function test_admin_service_detail_displays_on_hold_banner(): void
    {
        $this->service->update([
            'status' => 'ON_HOLD',
            'internal_notes' => 'Special filter delivery delay',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('services.show', $this->service->id));

        $response->assertStatus(200);
        $response->assertSee('ON HOLD');
        $response->assertSee('Service On Hold');
        $response->assertSee('Special filter delivery delay');
        $response->assertSee('Resume Service');
    }

    /**
     * Test admin can update service status to ON HOLD and resume via admin endpoint.
     */
    public function test_admin_can_update_service_status_to_on_hold_and_resume(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('services.status', $this->service->id), [
                'status' => 'ON_HOLD',
                'reason' => 'Put on hold by dispatcher',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ON_HOLD',
            ]);

        $this->service->refresh();
        $this->assertEquals('ON_HOLD', $this->service->status);
        $this->assertEquals('ON_HOLD', $this->service->jobCard->status);

        // Resume via admin
        $resumeResponse = $this->actingAs($this->adminUser)
            ->postJson(route('services.status', $this->service->id), [
                'status' => 'IN_PROGRESS',
                'reason' => 'Resumed by admin dispatcher',
            ]);

        $resumeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'IN_PROGRESS',
            ]);

        $this->service->refresh();
        $this->assertEquals('IN_PROGRESS', $this->service->status);
        $this->assertEquals('IN_PROGRESS', $this->service->jobCard->status);
    }
}
