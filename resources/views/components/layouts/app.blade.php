<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />

    <title>{{ config('app.name') }}</title>
    <meta name="description" content="A gradient palette generator for Minecraft. Quickly find matching blocks between two other blocks." />
    @vite('resources/css/app.css')
</head>
<body class="m-6 bg-stone-300">
    {{ $slot }}
</body>
</html>
