<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_seeded_cv_and_hides_a_section(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Amon Pratama')
            ->assertSee('Software engineer untuk produk web')
            ->assertSee('og:title', false);

        Section::query()->where('key', 'projects')->update([
            'is_visible' => false,
            'title' => 'Proyek tersembunyi khusus',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Proyek tersembunyi khusus');
    }

    public function test_admin_can_sign_in_and_change_the_headline(): void
    {
        $this->seed();

        $this->getJson('/admin/api/me')->assertUnauthorized();

        $this->postJson('/admin/api/login', [
            'email' => 'amon@teslatech.my.id',
            'password' => 'gantengmax',
        ])->assertOk()->assertJsonPath('user.email', 'amon@teslatech.my.id');

        $this->post('/admin/api/profile', [
            'name' => 'Amon Pratama',
            'headline' => 'Headline yang baru saja diubah',
            'summary' => Profile::query()->first()->summary,
            'bio' => Profile::query()->first()->bio,
        ])->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('Headline yang baru saja diubah');
    }

    public function test_guest_is_sent_to_the_admin_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }
}
