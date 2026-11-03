<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        @php
            $widthClass = $width === 'lg' ? 'max-w-2xl' : 'max-w-md';
        @endphp

        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-6">
            <a href="/" class="mb-5 block sm:mb-6">
                <img src="{{ asset(rawurlencode('Logo Foodbridge.webp')) }}" alt="FoodBridge" class="h-14 w-auto max-w-full object-contain sm:h-16" />
            </a>

            <main class="w-full {{ $widthClass }} rounded-lg border border-border bg-card p-6">
                {{ $slot }}
            </main>

            <p class="mt-5 text-xs text-muted-foreground">&copy; {{ date('Y') }} FoodBridge</p>
        </div>
    </body>
</html>
