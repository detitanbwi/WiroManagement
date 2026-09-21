<?php

namespace Tests\Feature;

use App\Jobs\SendProjectQcSummaryJob;
use App\Mail\ProjectQcSummaryMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QcSummarySyncEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_send_summary_email_endpoint_executes_synchronously(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create([
            'email' => 'superadmin@wirodev.com',
            'is_active' => true,
        ]);
        $superAdmin->syncRoles(['superadmin']);

        $client = Client::create(['name' => 'PT Test', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Sync Email Test',
            'project_code' => 'PRJ-SYNC-01',
            'status' => 'in_progress',
        ]);

        $developer = User::factory()->create([
            'name' => 'Developer One',
            'email' => 'dev1@corporate.com',
            'personal_email' => 'dev1.personal@gmail.com',
            'is_active' => true,
        ]);
        $project->users()->attach($developer->id);

        $response = $this->actingAs($superAdmin)
            ->postJson(route('api.qc.project.send-summary-email', $project->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'sent_count' => 1,
            'failed_count' => 0,
        ]);
        $this->assertStringContainsString('Ringkasan QA/QC berhasil dikirim ke 1 anggota proyek', $response->json('message'));

        // Verify sent immediately via Mail::send, NOT Mail::queue
        Mail::assertSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('dev1.personal@gmail.com');
        });
        Mail::assertNothingQueued();
    }

    public function test_send_summary_command_executes_synchronously(): void
    {
        Mail::fake();

        $client = Client::create(['name' => 'PT Test 2', 'email' => 'client2@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Command Sync Test',
            'project_code' => 'PRJ-SYNC-02',
            'status' => 'in_progress',
        ]);

        $qcUser = User::factory()->create([
            'name' => 'QC Specialist',
            'email' => 'qc@corporate.com',
            'personal_email' => null,
            'is_active' => true,
        ]);
        $project->users()->attach($qcUser->id);

        $this->artisan('qc:send-summary', ['project' => (string) $project->id])
            ->expectsOutputToContain('Sending QA/QC Summary synchronously')
            ->assertSuccessful();

        Mail::assertSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('qc@corporate.com');
        });
        Mail::assertNothingQueued();
    }

    public function test_send_project_qc_summary_job_returns_metrics_synchronously(): void
    {
        Mail::fake();

        $client = Client::create(['name' => 'PT Test 3', 'email' => 'client3@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Job Direct Sync Test',
            'project_code' => 'PRJ-SYNC-03',
            'status' => 'in_progress',
        ]);

        $member = User::factory()->create([
            'name' => 'Member Test',
            'email' => 'member@test.com',
            'is_active' => true,
        ]);
        $project->users()->attach($member->id);

        $result = SendProjectQcSummaryJob::dispatchSync($project);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['sent']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(1, $result['total']);

        Mail::assertSent(ProjectQcSummaryMail::class, 1);
        Mail::assertNothingQueued();
    }
}
