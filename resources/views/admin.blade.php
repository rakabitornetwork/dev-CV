<!DOCTYPE html>
<html lang="id" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Panel CV</title>
        <meta name="theme-color" content="#0c0c0a">
        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/admin/main.js'])
        @endif
    </head>
    <body class="bg-cv-bg text-cv-ink antialiased">
        <div id="admin-root"></div>
    </body>
</html>
