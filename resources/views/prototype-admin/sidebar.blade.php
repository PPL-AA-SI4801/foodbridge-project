<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>FoodBridge - Admin Sidebar Prototype ({{ $variant }})</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-background">
        @if ($variant === 'A')
            @include('prototype-admin.variants.variant-a')
        @elseif ($variant === 'B')
            @include('prototype-admin.variants.variant-b')
        @elseif ($variant === 'C')
            @include('prototype-admin.variants.variant-c')
        @endif

        @include('prototype-admin.partials.switcher', ['variant' => $variant, 'variants' => $variants])
    </body>
</html>
