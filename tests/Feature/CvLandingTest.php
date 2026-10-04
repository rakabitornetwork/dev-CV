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

    public function test_cv_download_is_built_from_the_profile(): void
    {
        $this->seed();

        Profile::query()->update([
            'name' => 'Achmad Nurohman',
            'location' => 'Indramayu, Indonesia',
            'cv_pdf_path' => 'cv/amon-pratama.pdf',
        ]);

        $response = $this->get(route('cv.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'achmad-nurohman-cv.pdf',
            (string) $response->headers->get('content-disposition'),
        );

        $text = str_replace("\0", '', $this->pdfText($response->getContent()));
        $text = preg_replace('/\s+/', '', $text) ?? '';
        $this->assertTrue(str_contains($text, 'AchmadNurohman'), 'PDF is missing the profile name.');
        $this->assertTrue(str_contains($text, 'Indramayu,Indonesia'), 'PDF is missing the profile location.');
        $this->assertFalse(str_contains($text, 'CVAmonPratama'), 'PDF still contains the placeholder title.');
    }

    private function pdfText(string $pdf): string
    {
        $text = '';

        if (! preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
            return $pdf;
        }

        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream) ?: @gzinflate($stream);
            $text .= is_string($decoded) ? $decoded : $stream;
        }

        return $text;
    }

    public function test_guest_is_sent_to_the_admin_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }
}
