<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />

    <title>{{ config('app.name') }}</title>
    <meta
        name="description"
        content="A gradient palette generator for Minecraft. Quickly find matching blocks between two other blocks."
    />
    
    <link rel="icon" href="https://fav.farm/💎" />
    
    @vite('resources/css/app.css')
    @production
        <script defer src="https://c3po.wacg.dev/protocol.js" data-website-id="31fca00c-9f17-4570-bd70-bd58b85b22af"></script>
    @endproduction
</head>
<body class="m-6 bg-stone-300 text-stone-700 lg:m-12">
    {{ $slot }}
</body>
</html>
