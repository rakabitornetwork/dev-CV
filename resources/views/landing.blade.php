<!DOCTYPE html>
<html lang="id" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ data_get($cv, 'profile.seo_title') ?: 'Curriculum Vitae' }}</title>
        <meta name="description" content="{{ data_get($cv, 'profile.seo_description') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ data_get($cv, 'profile.seo_title') ?: 'Curriculum Vitae' }}">
        <meta property="og:description" content="{{ data_get($cv, 'profile.seo_description') }}">
        @if (data_get($cv, 'profile.photo_url'))
            <meta property="og:image" content="{{ data_get($cv, 'profile.photo_url') }}">
        @endif
        <meta name="theme-color" content="#0c0c0a">
        <script>
            (function () {
                if (localStorage.getItem('cv-theme') === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/public/main.jsx'])
        @endif
    </head>
    <body class="bg-cv-bg text-cv-ink antialiased">
        <div id="cv-root"></div>
        <script>
            window.__CV__ = @json($cv);
        </script>
    </body>
</html>
