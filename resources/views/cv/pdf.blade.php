@php
    $profile = $cv['profile'];
    $keys = collect($cv['sections'])->pluck('key');
    $titleFor = function (string $key, string $fallback) use ($cv) {
        $section = collect($cv['sections'])->firstWhere('key', $key);

        return $section['title'] ?? $fallback;
    };
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $month = function (?string $value) use ($months) {
        if (blank($value) || ! str_contains($value, '-')) {
            return '';
        }
        [$year, $part] = explode('-', $value);

        return trim(($months[((int) $part) - 1] ?? '').' '.$year);
    };
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 36px 40px; }
            body { font-family: DejaVu Sans, sans-serif; color: #1c1a16; font-size: 11px; line-height: 1.45; }
            h1 { font-size: 24px; line-height: 1.1; margin: 0 0 4px; }
            h2 { font-size: 13px; letter-spacing: 1px; text-transform: uppercase; color: #8a5a32; margin: 18px 0 8px; border-bottom: 1px solid #e4d8c8; padding-bottom: 4px; }
            p { margin: 0 0 8px; }
            .headline { color: #5c564c; font-size: 12px; margin-bottom: 8px; }
            .meta { color: #5c564c; font-size: 10px; }
            table.header { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
            table.header td { vertical-align: top; }
            .photo { width: 92px; }
            .photo img { width: 84px; height: 105px; object-fit: cover; }
            .role { font-weight: bold; font-size: 12px; }
            .muted { color: #5c564c; }
            .item { margin-bottom: 10px; }
            .skill { margin-bottom: 3px; }
        </style>
    </head>
    <body>
        <table class="header">
            <tr>
                <td>
                    <h1>{{ $profile['name'] }}</h1>
                    <p class="headline">{{ $profile['headline'] }}</p>
                    <p class="meta">
                        {{ collect([$profile['location'], $profile['email'], $profile['phone'], $profile['availability_label']])->filter()->implode('  ·  ') }}
                    </p>
                </td>
                @if ($photoPath)
                    <td class="photo" align="right">
                        <img src="{{ $photoPath }}" alt="">
                    </td>
                @endif
            </tr>
        </table>

        @if (filled($profile['summary']))
            <p>{{ $profile['summary'] }}</p>
        @endif

        @if ($keys->contains('about') && filled($profile['bio']))
            <h2>{{ $titleFor('about', 'Tentang') }}</h2>
            @foreach (preg_split("/\n+/", $profile['bio']) as $paragraph)
                @if (filled($paragraph))
                    <p>{{ $paragraph }}</p>
                @endif
            @endforeach
        @endif

        @if ($keys->contains('experience') && count($cv['experiences']))
            <h2>{{ $titleFor('experience', 'Pengalaman') }}</h2>
            @foreach ($cv['experiences'] as $item)
                <div class="item">
                    <div class="role">{{ $item['role'] }} — {{ $item['company'] }}</div>
                    <div class="muted">
                        {{ $month($item['start_date']) }} — {{ $item['is_current'] ? 'Sekarang' : $month($item['end_date']) }}
                        @if (filled($item['location']))
                            · {{ $item['location'] }}
                        @endif
                    </div>
                    <p>{{ $item['description'] }}</p>
                </div>
            @endforeach
        @endif

        @if ($keys->contains('education') && count($cv['educations']))
            <h2>{{ $titleFor('education', 'Pendidikan') }}</h2>
            @foreach ($cv['educations'] as $item)
                @php
                    $degree = trim((string) $item['degree']);
                    $degree = in_array($degree, ['-', '–', '—'], true) ? '' : $degree;
                @endphp
                <div class="item">
                    <div class="role">{{ $degree }}@if (filled($degree) && filled($item['field'])) · @endif{{ $item['field'] }}</div>
                    <div class="muted">{{ $item['school'] }} · {{ $item['start_year'] }}@if ($item['end_year']) — {{ $item['end_year'] }}@endif</div>
                    @if (filled($item['description']))
                        <p>{{ $item['description'] }}</p>
                    @endif
                </div>
            @endforeach
        @endif

        @if ($keys->contains('skills') && count($cv['skills']))
            <h2>{{ $titleFor('skills', 'Keahlian') }}</h2>
            @foreach ($cv['skills'] as $item)
                <div class="skill">{{ $item['name'] }}@if (filled($item['category'])) · {{ $item['category'] }}@endif — {{ $item['level'] }}/100</div>
            @endforeach
        @endif

        @if ($keys->contains('projects') && count($cv['projects']))
            <h2>{{ $titleFor('projects', 'Proyek') }}</h2>
            @foreach ($cv['projects'] as $item)
                <div class="item">
                    <div class="role">{{ $item['title'] }}</div>
                    <p>{{ $item['summary'] }}</p>
                    @if (! empty($item['tech_stack']))
                        <div class="muted">{{ implode(', ', $item['tech_stack']) }}</div>
                    @endif
                </div>
            @endforeach
        @endif
    </body>
</html>
