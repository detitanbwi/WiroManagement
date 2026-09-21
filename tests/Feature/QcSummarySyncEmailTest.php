<?php

namespace Tests\Feature;

use App\Jobs\SendProjectQcSummaryJob;
use App\Mail\ProjectQcSummaryMail;
use App\Models\Client;
use App\Models\Project;
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

    public function test_send_summary_email_uses_personal_email_and_skips_empty_personal_email(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create([
            'email' => 'superadmin@wirodev.com',
            'personal_email' => 'superadmin.personal@gmail.com',
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

        // User 1: Has personal_email
        $devWithPersonalEmail = User::factory()->create([
            'name' => 'Developer With Personal Email',
            'email' => 'dev1@corporate.com',
            'personal_email' => 'dev1.personal@gmail.com',
            'is_active' => true,
        ]);
        $project->users()->attach($devWithPersonalEmail->id);

        // User 2: Has login email, but personal_email is NULL
        $devWithoutPersonalEmail = User::factory()->create([
            'name' => 'Developer Without Personal Email',
            'email' => 'dev2@corporate.com',
            'personal_email' => null,
            'is_active' => true,
        ]);
        $project->users()->attach($devWithoutPersonalEmail->id);

        // User 3: Has login email, but personal_email is empty string
        $devWithBlankPersonalEmail = User::factory()->create([
            'name' => 'Developer With Blank Personal Email',
            'email' => 'dev3@corporate.com',
            'personal_email' => '   ',
            'is_active' => true,
        ]);
        $project->users()->attach($devWithBlankPersonalEmail->id);

        $response = $this->actingAs($superAdmin)
            ->postJson(route('api.qc.project.send-summary-email', $project->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'sent_count' => 1,
            'failed_count' => 0,
        ]);
        $this->assertStringContainsString('Ringkasan QA/QC berhasil dikirim ke 1 anggota proyek', $response->json('message'));

        // Verify sent ONLY to personal email
        Mail::assertSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('dev1.personal@gmail.com');
        });

        // Verify NOT sent to corporate email or users without personal email
        Mail::assertNotSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('dev1@corporate.com') 
                || $mail->hasTo('dev2@corporate.com') 
                || $mail->hasTo('dev3@corporate.com');
        });

        Mail::assertSent(ProjectQcSummaryMail::class, 1);
        Mail::assertNothingQueued();
    }

    public function test_send_summary_aborts_when_no_members_have_personal_email(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create([
            'email' => 'superadmin@wirodev.com',
            'personal_email' => 'superadmin.personal@gmail.com',
            'is_active' => true,
        ]);
        $superAdmin->syncRoles(['superadmin']);

        $client = Client::create(['name' => 'PT Test No Personal Email', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project No Personal Email',
            'project_code' => 'PRJ-NO-EMAIL',
            'status' => 'in_progress',
        ]);

        $member = User::factory()->create([
            'name' => 'Member No Personal Email',
            'email' => 'member@corporate.com',
            'personal_email' => null,
            'is_active' => true,
        ]);
        $project->users()->attach($member->id);

        $response = $this->actingAs($superAdmin)
            ->postJson(route('api.qc.project.send-summary-email', $project->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Tidak ditemukan anggota proyek aktif yang memiliki alamat personal email untuk menerima ringkasan.',
        ]);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_send_summary_command_sends_strictly_to_personal_email(): void
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
            'personal_email' => 'qc.personal@gmail.com',
            'is_active' => true,
        ]);
        $project->users()->attach($qcUser->id);

        $this->artisan('qc:send-summary', ['project' => (string) $project->id])
            ->expectsOutputToContain('Sending QA/QC Summary synchronously')
            ->assertSuccessful();

        Mail::assertSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('qc.personal@gmail.com');
        });
        Mail::assertNotSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('qc@corporate.com');
        });
        Mail::assertNothingQueued();
    }

    public function test_send_project_qc_summary_job_skips_users_without_personal_email(): void
    {
        Mail::fake();

        $client = Client::create(['name' => 'PT Test 3', 'email' => 'client3@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Job Direct Sync Test',
            'project_code' => 'PRJ-SYNC-03',
            'status' => 'in_progress',
        ]);

        // One with personal email, one without
        $member1 = User::factory()->create([
            'name' => 'Member One',
            'email' => 'member1@test.com',
            'personal_email' => 'member1.personal@gmail.com',
            'is_active' => true,
        ]);
        $member2 = User::factory()->create([
            'name' => 'Member Two',
            'email' => 'member2@test.com',
            'personal_email' => null,
            'is_active' => true,
        ]);
        $project->users()->attach([$member1->id, $member2->id]);

        $result = SendProjectQcSummaryJob::dispatchSync($project);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['sent']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(1, $result['total']);

        Mail::assertSent(ProjectQcSummaryMail::class, 1);
        Mail::assertSent(ProjectQcSummaryMail::class, function ($mail) {
            return $mail->hasTo('member1.personal@gmail.com');
        });
        Mail::assertNothingQueued();
    }
}
