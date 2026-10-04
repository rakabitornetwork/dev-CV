<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Section;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'amon@teslatech.my.id'],
            [
                'name' => 'Amon',
                'password' => 'gantengmax',
                'email_verified_at' => now(),
            ],
        );

        User::query()->where('email', 'test@example.com')->delete();

        foreach ([Testimonial::class, Project::class, Skill::class, Education::class, Experience::class, SocialLink::class, Section::class, Profile::class] as $model) {
            $model::query()->delete();
        }

        $this->publishPhotos();

        Profile::query()->create([
            'name' => 'Amon Pratama',
            'headline' => 'Software engineer untuk produk web yang tenang, cepat, dan mudah diubah.',
            'summary' => 'Saya merancang sistem dari skema data sampai antarmuka. Sepuluh tahun terakhir saya membantu tim merilis produk dengan Laravel, React, dan Vue tanpa mengorbankan keterbacaan kode.',
            'bio' => "Saya tumbuh sebagai engineer di tim kecil yang harus mengerjakan semuanya: basis data, API, dan layar yang dipakai orang setiap hari. Kebiasaan itu membuat saya peduli pada batas yang jelas antara data, aturan bisnis, dan tampilan.\n\nDi luar jam kerja saya menulis catatan arsitektur yang singkat, meninjau antarmuka supaya tetap enak dipakai di layar sempit, dan menjaga rilis tetap bisa ditelusuri. Saya paling nyaman saat sebuah produk terasa sederhana dari luar, sementara bagian dalamnya tetap rapi untuk orang yang akan merawatnya.",
            'photo_path' => 'cv/portrait.jpg',
            'location' => 'Jakarta, Indonesia',
            'email' => 'amon@teslatech.my.id',
            'phone' => '+62 812-3456-7890',
            'availability_label' => 'Tersedia untuk kolaborasi',
            'seo_title' => 'Amon Pratama — Software Engineer',
            'seo_description' => 'Curriculum vitae Amon Pratama, software engineer di Jakarta. Pengalaman membangun produk web dengan Laravel, React, dan Vue.',
            'cv_pdf_path' => null,
        ]);

        $sections = [
            ['key' => 'about', 'title' => 'Tentang'],
            ['key' => 'experience', 'title' => 'Pengalaman'],
            ['key' => 'education', 'title' => 'Pendidikan'],
            ['key' => 'skills', 'title' => 'Keahlian'],
            ['key' => 'projects', 'title' => 'Proyek'],
            ['key' => 'testimonials', 'title' => 'Testimoni'],
            ['key' => 'contact', 'title' => 'Kontak'],
        ];

        foreach ($sections as $index => $section) {
            Section::query()->create([
                ...$section,
                'is_visible' => true,
                'sort_order' => $index + 1,
            ]);
        }

        $socials = [
            ['label' => 'GitHub', 'icon' => 'Github', 'url' => 'https://github.com/amonpratama'],
            ['label' => 'LinkedIn', 'icon' => 'Linkedin', 'url' => 'https://www.linkedin.com/in/amonpratama'],
            ['label' => 'Email', 'icon' => 'Mail', 'url' => 'mailto:amon@teslatech.my.id'],
            ['label' => 'Situs', 'icon' => 'Globe', 'url' => 'https://teslatech.my.id'],
        ];

        foreach ($socials as $index => $social) {
            SocialLink::query()->create([...$social, 'is_visible' => true, 'sort_order' => $index + 1]);
        }

        $experiences = [
            [
                'role' => 'Staff Engineer',
                'company' => 'Arunika Digital',
                'location' => 'Jakarta',
                'start_date' => '2023-03-01',
                'end_date' => null,
                'is_current' => true,
                'description' => 'Memimpin perancangan platform internal untuk operasional klien. Saya menyatukan API Laravel, dasbor React, dan antrean pekerjaan supaya rilis mingguan tetap bisa diaudit.',
            ],
            [
                'role' => 'Software Engineer',
                'company' => 'Langit Putih Studio',
                'location' => 'Bandung',
                'start_date' => '2020-01-01',
                'end_date' => '2023-02-01',
                'is_current' => false,
                'description' => 'Membangun situs produk dan panel konten untuk studio desain. Fokusnya pada alur editor yang ringan dan halaman publik yang tetap cepat di jaringan yang tidak stabil.',
            ],
            [
                'role' => 'Junior Developer',
                'company' => 'Bengkel Kode',
                'location' => 'Yogyakarta',
                'start_date' => '2018-07-01',
                'end_date' => '2019-12-01',
                'is_current' => false,
                'description' => 'Merawat aplikasi pesanan bengkel: dari formulir servis, status pengerjaan, sampai laporan harian yang bisa dibaca pemilik tanpa pelatihan panjang.',
            ],
        ];

        foreach ($experiences as $index => $experience) {
            Experience::query()->create([...$experience, 'is_visible' => true, 'sort_order' => $index + 1]);
        }

        Education::query()->create([
            'school' => 'Universitas Gadjah Mada',
            'degree' => 'Sarjana Komputer',
            'field' => 'Teknik Informatika',
            'start_year' => 2014,
            'end_year' => 2018,
            'description' => 'Tugas akhir tentang antarmuka pencarian arsip yang tetap bisa dipakai di layar kecil.',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $skills = [
            ['name' => 'Laravel', 'level' => 92, 'category' => 'Backend'],
            ['name' => 'React', 'level' => 88, 'category' => 'Antarmuka'],
            ['name' => 'Vue', 'level' => 84, 'category' => 'Antarmuka'],
            ['name' => 'PostgreSQL', 'level' => 80, 'category' => 'Data'],
            ['name' => 'Tailwind CSS', 'level' => 86, 'category' => 'Antarmuka'],
            ['name' => 'REST API', 'level' => 90, 'category' => 'Backend'],
        ];

        foreach ($skills as $index => $skill) {
            Skill::query()->create([...$skill, 'is_visible' => true, 'sort_order' => $index + 1]);
        }

        $projects = [
            [
                'title' => 'Portal Arsip Desa',
                'summary' => 'Arsip keputusan desa yang bisa dicari, diunduh, dan ditinjau perangkat tanpa akun yang rumit.',
                'url' => 'https://contoh.teslatech.my.id/arsip',
                'tech_stack' => ['Laravel', 'Vue', 'SQLite'],
                'cover_path' => 'cv/projects/arsip.jpg',
            ],
            [
                'title' => 'Dasbor Operasional Bengkel',
                'summary' => 'Papan status servis untuk mekanik dan kasir, dengan antrean yang tetap jelas saat jam sibuk.',
                'url' => 'https://contoh.teslatech.my.id/bengkel',
                'tech_stack' => ['Laravel', 'React', 'PostgreSQL'],
                'cover_path' => 'cv/projects/bengkel.jpg',
            ],
            [
                'title' => 'Katalog Produk Studio',
                'summary' => 'Katalog editorial untuk studio desain, lengkap dengan panel yang mengatur urutan karya.',
                'url' => 'https://contoh.teslatech.my.id/katalog',
                'tech_stack' => ['Laravel', 'React', 'Tailwind'],
                'cover_path' => 'cv/projects/katalog.jpg',
            ],
        ];

        foreach ($projects as $index => $project) {
            Project::query()->create([...$project, 'is_visible' => true, 'sort_order' => $index + 1]);
        }

        $testimonials = [
            [
                'name' => 'Sari Rahma',
                'role' => 'Kepala Produk',
                'company' => 'Arunika Digital',
                'quote' => 'Amon membuat sistem yang rumit terasa tenang dipakai. Rilis kami jadi lebih mudah dijelaskan ke tim lain.',
                'avatar_path' => 'cv/avatars/sari.jpg',
            ],
            [
                'name' => 'Dimas Wicaksono',
                'role' => 'Pendiri',
                'company' => 'Bengkel Kode',
                'quote' => 'Panel yang ia buat langsung dipakai mekanik di hari pertama. Tidak ada pelatihan yang bertele-tele.',
                'avatar_path' => 'cv/avatars/dimas.jpg',
            ],
            [
                'name' => 'Laras Putri',
                'role' => 'Direktur Kreatif',
                'company' => 'Langit Putih Studio',
                'quote' => 'Ia menjaga halaman tetap terasa seperti karya studio, bukan template. Urutan konten pun bisa kami atur sendiri.',
                'avatar_path' => 'cv/avatars/laras.jpg',
            ],
        ];

        foreach ($testimonials as $index => $testimonial) {
            Testimonial::query()->create([...$testimonial, 'is_visible' => true, 'sort_order' => $index + 1]);
        }
    }

    private function publishPhotos(): void
    {
        $files = [
            'portrait.jpg' => 'cv/portrait.jpg',
            'arsip.jpg' => 'cv/projects/arsip.jpg',
            'bengkel.jpg' => 'cv/projects/bengkel.jpg',
            'katalog.jpg' => 'cv/projects/katalog.jpg',
            'sari.jpg' => 'cv/avatars/sari.jpg',
            'dimas.jpg' => 'cv/avatars/dimas.jpg',
            'laras.jpg' => 'cv/avatars/laras.jpg',
        ];

        foreach ($files as $source => $target) {
            Storage::disk('public')->put(
                $target,
                file_get_contents(database_path('seeders/assets/'.$source)),
            );
        }
    }

}
