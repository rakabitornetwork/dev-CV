<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Deployer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_the_update_page_or_api(): void
    {
        $this->get('/admin/pembaruan')->assertRedirect('/admin/login');
        $this->getJson('/admin/api/updates')->assertUnauthorized();
        $this->postJson('/admin/api/updates', [
            'confirm' => true,
            'mode' => 'update',
        ])->assertUnauthorized();
    }

    public function test_admin_can_see_the_installed_version_without_deploying(): void
    {
        $this->signIn();

        $this->getJson('/admin/api/updates')
            ->assertOk()
            ->assertJsonPath('branch', 'main')
            ->assertJsonPath('repository', 'rakabitornetwork/dev-CV')
            ->assertJsonStructure(['commit' => ['short', 'subject'], 'job' => ['state']]);
    }

    public function test_admin_must_confirm_before_an_update_is_accepted(): void
    {
        $this->signIn();

        $this->postJson('/admin/api/updates', [
            'mode' => 'update',
        ])->assertUnprocessable();

        $this->postJson('/admin/api/updates', [
            'confirm' => true,
            'mode' => 'update',
        ])->assertAccepted()
            ->assertJsonPath('state', 'success')
            ->assertJsonPath('message', 'Pembaruan tidak dijalankan saat pengujian.');
    }

    public function test_update_pipeline_pulls_before_the_build(): void
    {
        $lines = collect(app(Deployer::class)->pipeline(false))
            ->map(fn (array $step) => implode(' ', $step['command']))
            ->implode("\n");

        $this->assertLessThan(strpos($lines, 'run build'), strpos($lines, 'pull --ff-only'));
        $this->assertStringContainsString('migrate --force', $lines);
        $this->assertStringNotContainsString('--no-dev', $lines);
    }

    public function test_rebuild_pipeline_does_not_pull(): void
    {
        $lines = collect(app(Deployer::class)->pipeline(true))
            ->map(fn (array $step) => implode(' ', $step['command']))
            ->implode("\n");

        $this->assertStringNotContainsString('pull', $lines);
        $this->assertStringContainsString('run build', $lines);
    }

    private function signIn(): void
    {
        User::factory()->create([
            'email' => 'amon@teslatech.my.id',
            'password' => 'gantengmax',
        ]);

        $this->postJson('/admin/api/login', [
            'email' => 'amon@teslatech.my.id',
            'password' => 'gantengmax',
        ])->assertOk();
    }
}
